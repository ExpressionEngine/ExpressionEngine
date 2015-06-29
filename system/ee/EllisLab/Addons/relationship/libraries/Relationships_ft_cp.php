<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.6
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Relationship Fieldtype Settings Helper Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Relationships_ft_cp {

	/**
	 * Create a settings form object
	 *
	 * @param	array
	 *		field_name => empty value
	 * @param	form prefix
	 * @return	Object<Relationship_settings_form>
	 */
	public function form($data, $prefix = '')
	{
		return new Relationship_settings_form($data, $prefix);
	}

	// --------------------------------------------------------------------

	/**
	 * Grab all channels, across sites if appropriate.
	 *
	 * @return	array
	 *		channel_id => site_label - channel_title
	 */
	public function all_channels()
	{
		$from_all_sites = (ee()->config->item('multiple_sites_enabled') == 'y');

		$channels = ee('Model')->get('Channel')
			->order('channel_title', 'asc');

		if ( ! $from_all_sites)
		{
			$channels->filter('site_id', 1);
		}

		if ($from_all_sites)
		{
			$channel_choices = array();

			foreach ($channels->all() as $channel)
			{
				$channel_choices[$channel->channel_id] = $channel->channel_title . '<i>&mdash; ' . $channel->Site->site_label . '</i>';
			}
		}
		else
		{
			$channel_choices = $channels->getDictionary('channel_id', 'channel_title');
		}

		return array_merge($this->_form_any(), $channel_choices);
	}

	// --------------------------------------------------------------------

	/**
	 * Grab all categories, across sites if appropriate.
	 *
	 * @return	Object <TreeIterator>
	 */
	public function all_categories()
	{
		$from_all_sites = (ee()->config->item('multiple_sites_enabled') == 'y');

		$categories = ee('Model')->get('Category')
			->order('group_id', 'asc')
			->order('parent_id', 'asc')
			->order('cat_name', 'asc');

		if ( ! $from_all_sites)
		{
			$categories->filter('site_id', 1);
		}

		return array_merge($this->_form_any(), $categories->all()->getDictionary('cat_id', 'cat_name'));
	}

	// --------------------------------------------------------------------

	/**
	 * Grab all possible authors (individuals and member groups)
	 *
	 * @return	array
	 *		id => name (id can either be g_# or m_# for group or member ids)
	 */
	public function all_authors()
	{
		$from_all_sites = (ee()->config->item('multiple_sites_enabled') == 'y');

		$prefix = ee()->db->dbprefix;

		if ( ! $from_all_sites)
		{
			ee()->db->where('site_id', '1');
		}

		// First the author groups
		$groups = ee('Model')->get('MemberGroup')
			->fields('group_id', 'group_title')
			->filter('include_in_authorlist', 'y')
			->order('group_title', 'asc');

		if ( ! $from_all_sites)
		{
			$groups->filter('site_id', '1');
		}

		$groups = $groups->all();
		$group_ids = $groups->pluck('group_id');

		// Then all authors who are in those groups or who have author access
		$members = ee('Model')->get('Member')
			->fields('member_id', 'group_id', 'screen_name', 'username')
			->filter('in_authorlist', 'y')
			->order('screen_name', 'asc')
			->order('username', 'asc');

		if ($groups->count())
		{
			$members->orFilter('group_id', 'IN', $group_ids);
		}

		$group_to_member = array_fill_keys($group_ids, array());

		foreach ($members->all() as $m)
		{
			$group_to_member[$m->group_id][] = $m;
		}

		$indent = str_repeat(NBS, 4);

		$authors = $this->_form_any();

		// Reoder by groups with subitems for authors
		foreach ($groups as $group)
		{
			$authors['g_'.$group->group_id] = $group->group_title;

			foreach ($group_to_member[$group->group_id] as $m)
			{
				$authors['m_'.$m->member_id] = $indent.(($m->screen_name == '') ? $m->username : $m->screen_name);
			}
		}

		return $authors;
	}

	// --------------------------------------------------------------------

	/**
	 * Grab all statuses
	 *
	 * @return	array
	 *		id => name
	 */
	public function all_statuses()
	{
		$from_all_sites = (ee()->config->item('multiple_sites_enabled') == 'y');

		$statuses = ee('Model')->get('Status')
			->order('status_id', 'asc');

		if ( ! $from_all_sites)
		{
			$statuses->filter('site_id', 1);
		}

		$status_options = $this->_form_any();

		foreach ($statuses->all() as $status)
		{
			$status_name = ($status->status == 'closed' OR $status->status == 'open') ?  lang($status->status) : $status->status;
			$status_options[$status->status] = $status_name;
		}

		return $status_options;
	}

	// --------------------------------------------------------------------

	/**
	 * Returns our possible ordering columns
	 *
	 * @return	array [column => human name]
	 */
	public function all_order_options()
	{
		return array(
			'title' 	 => lang('rel_ft_order_title'),
			'entry_date' => lang('rel_ft_order_date')
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Returns our possible ordering directions
	 *
	 * @return	array [dir => human name]
	 */
	public function all_order_directions()
	{
		return array(
			'asc' => lang('rel_ft_order_asc'),
			'desc'	=> lang('rel_ft_order_desc'),
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Default multiselect data (-- Any --)
	 *
	 * @return	array
	 */
	protected function _form_any()
	{
		return array('--' => '-- Any --');
	}
}


// ------------------------------------------------------------------------

/**
 * Settings Form Class
 *
 * Handles form population, default values, field prefixes etc. Passes
 * everything on to the form helper for actual creation.
 */
class Relationship_settings_form {

	protected $_prefix = '';
	protected $_fields = array();
	protected $_options = array();
	protected $_selected = array();

	public function __construct(array $defaults, $prefix)
	{
		$this->_fields = $defaults;
		$this->_prefix = $prefix ? $prefix.'_' : '';
	}

	// --------------------------------------------------------------------

	/**
	 * Get the current form values for all fields
	 *
	 * @return	array [form_name => value]
	 */
	public function values()
	{
		return $this->_selected;
	}

	// --------------------------------------------------------------------

	/**
	 * Populate the form with values
	 *
	 * @param	array
	 *		field_name => value
	 * @return	self
	 */
	public function populate($data)
	{
		$rename = preg_grep('/^'.$this->_prefix.'.*/i', array_keys($data));

		foreach ($rename as $key)
		{
			$new_key = substr($key, strlen($this->_prefix));
			$data[$new_key] = $data[$key];
			unset($data[$key]);
		}

		$data = array_intersect_key($data, $this->_fields);
		$this->_selected = array_merge($this->_selected, $this->_fields, $data);

		// Bug 19321: Old relationship fields use "date" instead of "entry_date"
		if (isset($data['order_field']) && $data['order_field'] == 'date')
		{
			$data['order_field'] = 'entry_date';
		}

		// array_merge_recursive($this->_fields, $data) without
		// php's super weird recursive merging on on arrays

		foreach ($data as $k => $v)
		{
			if (is_array($this->_fields[$k]))
			{
				$this->_selected[$k] = array_merge($this->_fields[$k], $v);
			}
		}

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Set possible options for dropdowns and multiselects
	 *
	 * @param	array
	 *		field_name => options
	 * @return	self
	 */
	public function options($data)
	{
		$this->_options = array_intersect_key($data, $this->_fields);
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Pass calls to form names on to the form helper for html generation
	 *
	 * @param	function name
	 * @param	[form name, extras]
	 * @return	string of form element
	 */
	public function __call($fn, $args)
	{
		$args[1] = isset($args[1]) ? $args[1] : '';
		list($name, $extras) = $args;

		$prefix = $this->_prefix;

		// dropdowns
		if (in_array($fn, array('dropdown', 'multiselect')))
		{
			$full_name = $prefix.$name;

			if ($fn == 'multiselect')
			{
				$full_name .= '[]';
			}

			if ( ! count($this->_selected[$name]))
			{
				$this->_selected[$name] = array('--');
			}

			$params = array(
				$full_name,
				$this->_options[$name],
				set_value($prefix.$name, $this->_selected[$name]),
				$extras
			);
		}
		elseif (in_array($fn, array('checkbox', 'radio')))
		{
			$params = array(
				$prefix.$name,
				1,
				set_value($prefix.$name, $this->_selected[$name]),
				$extras
			);
		}
		else
		{
			$params = array(
				$prefix.$name,
				set_value($prefix.$name, $this->_selected[$name]),
				$extras
			);
		}

		return call_user_func_array('form_'.$fn, $params);
	}

}
