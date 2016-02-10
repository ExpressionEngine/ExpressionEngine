<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.6
 * @filesource
 */

// --------------------------------------------------------------------

/**
 * ExpressionEngine Relationship Fieldtype Class
 *
 * @package		ExpressionEngine
 * @subpackage	Fieldtypes
 * @category	Fieldtypes
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Relationship_ft extends EE_Fieldtype {

	public $info = array(
		'name'		=> 'Relationships',
		'version'	=> '1.0'
	);

	public $has_array_data = FALSE;

	private $_table = 'relationships';

	// Cache variables
	protected $channels = array();
	protected $entries = array();
	protected $children = array();

	/**
	 * Validate Field
	 *
	 * @todo TODO check if ids are valid according to the settings.
	 *
	 * @param	field data
	 * @return	bool valid
	 */
	public function validate($data)
	{
		$data = isset($data['data']) ? $data['data'] : array();

		$set = array();

		foreach ($data as $i => $child_id)
		{
			if ( ! $child_id)
			{
				continue;
			}

			$set[] = $child_id;
		}

		if ($this->settings['field_required'] == 'y')
		{
			if ( ! count($set))
			{
				return lang('required');
			}
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Save Field
	 *
	 * In our case the actual field entry will be blank, so we'll simply
	 * cache some data for the post_save method.
	 *
	 * @param	field data
	 * @return	column data
	 */
	public function save($data, $model = NULl)
	{
		$sort = isset($data['sort']) ? $data['sort'] : array();
		$data = isset($data['data']) ? $data['data'] : array();

		$sort = array_filter($sort);

		$cache_name = $this->field_name;

		if (isset($this->settings['grid_row_name']))
		{
			$cache_name .= $this->settings['grid_row_name'];
		}

		if (isset($model))
		{
			$name = $this->field_name;
			$model->$name = '';
		}

		ee()->session->set_cache(__CLASS__, $cache_name, array(
			'data' => $data,
			'sort' => $sort
		));

		unset($_POST['sort_'.$this->field_name]);

		return '';
	}

	// --------------------------------------------------------------------

	/**
	 * Post field save is where we do the actual works since we store
	 * data in our own table based on the entry_id, which does not exist
	 * before saving.
	 *
	 * @param	the return value of save()
	 * @return	void
	 */
	public function post_save($data)
	{
		$field_id = $this->field_id;
		$entry_id = $this->content_id();

		$cache_name = $this->field_name;

		if (isset($this->settings['grid_row_name']))
		{
			$cache_name .= $this->settings['grid_row_name'];
		}

		$post = ee()->session->cache(__CLASS__, $cache_name);

		if ($post === FALSE)
		{
			// this is a channel:form edit - save() was not called. Don't do anything.
			return;
		}

		$order = array_values($post['sort']);
		$data = $post['data'];

		$all_rows_where = array(
			'parent_id' => $entry_id,
			'field_id' => $field_id
		);

		if (isset($this->settings['grid_field_id']))
		{
			// grid takes the parent grid's field id and sticks it into "grid_field_id"
			$all_rows_where['grid_col_id'] = $this->settings['col_id'];
			$all_rows_where['grid_field_id'] = $this->settings['grid_field_id'];
			$all_rows_where['grid_row_id'] = $this->settings['grid_row_id'];
		}

		// clear old stuff
		ee()->db
			->where($all_rows_where)
			->delete($this->_table);

		// insert new stuff
		$ships = array();

		foreach ($data as $i => $child_id)
		{
			if ( ! $child_id)
			{
				continue;
			}

			// the old data array
			$new_row = $all_rows_where;
			$new_row['child_id'] = $child_id;
			$new_row['order'] = isset($order[$i]) ? $order[$i] : 0;

			$ships[] = $new_row;
		}

		// -------------------------------------------
		// 'relationships_post_save' hook.
		//  - Allow developers to modify or add to the relationships array before saving
		//
			if (ee()->extensions->active_hook('relationships_post_save') === TRUE)
			{
				$ships = ee()->extensions->call('relationships_post_save', $ships, $entry_id, $field_id);
			}
		//
		// -------------------------------------------


		// If child_id is empty, they are deleting a single relationship
		if (count($ships))
		{
			ee()->db->insert_batch($this->_table, $ships);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Called when entries are deleted
	 *
	 * @access	public
	 * @param	array of entry ids to delete
	 */
	public function delete($ids)
	{
		ee()->db
			->where_in('parent_id', $ids)
			->or_where_in('child_id', $ids)
			->delete($this->_table);
	}


	// --------------------------------------------------------------------

	/**
	 * Called when grid entries are deleted
	 *
	 * @access	public
	 * @param	array of entry ids to delete
	 */
	public function grid_delete($ids)
	{
		ee()->db
			->where('field_id', $this->field_id)
			->where_in('grid_row_id', $ids)
			->delete($this->_table);
	}

	// --------------------------------------------------------------------

	/**
	 * Display the field on the publish page
	 *
	 * Show the field to the user. In this case that means either
	 * showing a dropdown for single selects or our custom multiselect
	 * interface.
	 *
	 * @param	stored data
	 * @return	interface string
	 */
	public function display_field($data)
	{
		$field_name = $this->field_name;

		$entry_id = ($this->content_id) ?: ee()->input->get('entry_id');

		$order = array();
		$entries = array();
		$selected = array();

		if (is_array($data) && isset($data['data']) && ! empty($data['data'])) // autosave
		{
			foreach ($data['data'] as $k => $id)
			{
				$selected[$id] = $id;
				$order[$id] = isset($data['sort'][$k]) ? $data['sort'][$k] : 0;
			}
		}
		elseif (is_int($data))
		{
			$selected[$data] = $data;
		}

		// Fetch existing related entries?
		$get_related = FALSE;
		if ($entry_id)
		{
			// If we have an entry_id then we are editing and likely need to
			// get related entries
			$get_related = TRUE;

			// If this relationship belongs to a grid and doesn't have a
			// row id, then we are not editing and should not look for
			// related entries.
			if (isset($this->settings['grid_field_id'])
				&& ! isset($this->settings['grid_row_id']))
			{
				$get_related = FALSE;
			}
		}

		if ($get_related)
		{
			$wheres = array(
				'parent_id' => $entry_id,
				'field_id' => $this->field_id,
			);

			if (isset($this->settings['grid_row_id']))
			{
				$wheres['grid_col_id'] = $this->settings['col_id'];
				$wheres['grid_field_id'] = $this->settings['grid_field_id'];
				$wheres['grid_row_id'] = $this->settings['grid_row_id'];
			}

			ee()->db
				->select('child_id, order')
				->from($this->_table)
				->where($wheres);

			// -------------------------------------------
			// 'relationships_display_field' hook.
			// - Allow developers to perform their own queries to modify which entries are retrieved
			//
			// 	There are 3 ways to use this hook:
			// 	 	1) Add to the existing Active Record call, e.g. ee()->db->where('foo', 'bar');
			// 	 	2) Call ee()->db->_reset_select(); to terminate this AR call and start a new one
			// 	 	3) Call ee()->db->_reset_select(); and modify the currently compiled SQL string
			//
			//   All 3 require a returned query result array.
			//
			if (ee()->extensions->active_hook('relationships_display_field') === TRUE)
			{
				$related = ee()->extensions->call(
					'relationships_display_field',
					$entry_id,
					$this->field_id,
					ee()->db->_compile_select(FALSE, FALSE)
				);
			}
			else
			{
				$related = ee()->db->get()->result_array();
			}
			//
			// -------------------------------------------

			foreach ($related as $row)
			{
				if ( ! AJAX_REQUEST)
				{
					$selected[$row['child_id']] = $row['child_id'];
				}

				$order[$row['child_id']] = $row['order'];
			}
		}

		$channels = array();
		$limit_channels = $this->settings['channels'];
		$limit_categories = $this->settings['categories'];
		$limit_statuses = $this->settings['statuses'];
		$limit_authors = $this->settings['authors'];
		$limit = $this->settings['limit'];

		$show_expired = (bool) $this->settings['expired'];
		$show_future = (bool) $this->settings['future'];

		$order_field = $this->settings['order_field'];
		$order_dir = $this->settings['order_dir'];

		$separate_query_for_selected = (count($selected) && $limit);

		// Create a cache ID based on the query criteria for this field so fields
		// with similar entry listings can share data that's already been queried
		$cache_id = md5(serialize(compact('limit_channels', 'limit_categories', 'limit_statuses',
			'limit_authors', 'limit', 'show_expired', 'show_future', 'order_field', 'order_dir')));

		// Bug 19321, old fields use date
		if ($order_field == 'date')
		{
			$order_field = 'entry_date';
		}

		$entries = ee('Model')->get('ChannelEntry')
			->with('Channel')
			->fields('Channel.*', 'entry_id', 'title', 'channel_id')
			->filter('site_id', ee()->config->item('site_id'))
			->order($order_field, $order_dir);

		if (AJAX_REQUEST)
		{
			if (ee()->input->post('search'))
			{
				$entries->filter('title', 'LIKE', '%' . ee()->input->post('search') . '%');
			}

			if (ee()->input->post('channel'))
			{
				$entries->filter('channel_id', ee()->input->post('channel'));
			}
		}

		// Create a cache of channel names
		if (empty($this->channels))
		{
			$this->channels = ee('Model')->get('Channel')
				->fields('channel_title')
				->all();
		}

		if (count($limit_channels))
		{
			$entries->filter('channel_id', 'IN', $limit_channels);
			$channels = $this->channels->filter(function($channel) use ($limit_channels)
			{
				return in_array($channel->getId(), $limit_channels);
			});
		}
		else
		{
			$channels = $this->channels;
		}

		if (count($limit_categories))
		{
			$entries->with('Categories')
				->filter('Categories.cat_id', 'IN', $limit_categories);
		}

		if (count($limit_statuses))
		{
			$limit_statuses = str_replace(
				array('Open', 'Closed'),
				array('open', 'closed'),
				$limit_statuses
			);

			$entries->filter('status', 'IN', $limit_statuses);
		}

		if (count($limit_authors))
		{
			$groups = array();
			$members = array();

			foreach ($limit_authors as $author)
			{
				switch ($author[0])
				{
					case 'g': $groups[] = substr($author, 2);
						break;
					case 'm': $members[] = substr($author, 2);
						break;
				}
			}

			$entries->with('Author');

			if (count($members) && count($groups))
			{
				$entries->filterGroup()
					->filter('author_id', 'IN', implode(', ', $members))
					->orFilter('Author.group_id', 'IN', implode(', ', $groups))
					->endFilterGroup();
			}
			else
			{
				if (count($members))
				{
					$entries->filter('author_id', 'IN', implode(', ', $members));
				}

				if (count($groups))
				{
					$entries->filter('Author.group_id', 'IN', implode(', ', $groups));
				}
			}
		}

		// Limit times
		$now = ee()->localize->now;

		if ( ! $show_future)
		{
			$entries->filter('entry_date', '<', $now);
		}

		if ( ! $show_expired)
		{
			$entries->filterGroup()
				->filter('expiration_date', 0)
				->orFilter('expiration_date', '>', $now)
				->endFilterGroup();
		}

		if ($entry_id)
		{
			$entries->filter('entry_id', '!=', $entry_id);
		}

		if ($limit)
		{
			$entries->limit($limit);
		}

		// If we've got a limit and selected entries, we need to run the query
		// twice. Once without those entries and then separately with only those
		// entries.

		if ($separate_query_for_selected)
		{
			$selected_entries = clone $entries;

			$entries = $entries->filter('entry_id', 'NOT IN', $selected)->all();

			$selected_entries->limit(count($selected))
				->filter('entry_id', 'IN', $selected)
				->all()
				->map(function($entry) use(&$entries) { $entries[] = $entry; });

			$entries = $entries->sortBy($order_field);
			if (strtolower($order_dir) == 'desc')
			{
				$entries = $entries->reverse();
			}
		}
		else
		{
			// Don't query if we have this same query in the cache
			if (isset($this->entries[$cache_id]))
			{
				$entries = $this->entries[$cache_id];
			}
			else
			{
				$this->entries[$cache_id] = $entries = $entries->all();
			}
		}

		if (REQ != 'CP' && $this->settings['allow_multiple'] == 0)
		{
			$options[''] = '--';

			foreach ($entries as $entry)
			{
				$options[$entry->entry_id] = $entry->title;
			}

			return form_dropdown($field_name.'[data][]', $options, current($selected));
		}

		ee()->cp->add_js_script(array(
			'plugin' => 'ee_interact.event',
			'file' => 'fields/relationship/cp',
			'ui' => 'sortable'
		));

		if ($entry_id)
		{
			if ( ! isset($this->children[$entry_id]))
			{
				// Cache children for this entry
				$this->children[$entry_id] = $children = ee('Model')->get('ChannelEntry', $entry_id)
					->with('Children')
					->first()
					->Children;
			}
			else
			{
				$children = $this->children[$entry_id];
			}

			if (AJAX_REQUEST)
			{
				if (ee()->input->post('search_related'))
				{
					$search_term = ee()->input->post('search_related');
					$children = $children->filter(function($entry) use($search_term) {
						return (strpos($entry->title, $search_term) !== FALSE);
					});
				}
			}

			$children = $children->indexBy('entry_id');
		}
		else
		{
			$children = array();
		}

		$entries = $entries->indexBy('entry_id');
		$children_ids = array_keys($children);
		$entry_ids = array_keys($entries);

		foreach ($selected as $chosen)
		{
			if ( ! in_array($chosen, $children_ids)
				&& in_array($chosen, $entry_ids))
			{
				$children[$chosen] = $entries[$chosen];
			}
		}

		asort($order);

		$related = array();

		$new_children_ids = array_diff(array_keys($order), $children_ids);
		$new_children = array();

		if ( ! empty($new_children_ids))
		{
			$new_children = ee('Model')->get('ChannelEntry', $new_children_ids)->with('Channel');

			if (AJAX_REQUEST)
			{
				if (ee()->input->post('search_related'))
				{
					$new_children->filter('title', 'LIKE', '%' . ee()->input->post('search_related') . '%');
				}
			}

			$new_children = $new_children->all()
				->indexBy('entry_id');
		}

		foreach ($order as $key => $index)
		{
			if (in_array($key, $children_ids))
			{
				$related[] = $children[$key];
			}
			elseif (isset($new_children[$key]))
			{
				$related[] = $new_children[$key];
			}
		}

		$multiple = (bool) $this->settings['allow_multiple'];

		return ee('View')->make('relationship:publish')->render(compact('field_name', 'entries', 'selected', 'related', 'multiple', 'channels'));
	}

	// --------------------------------------------------------------------

	/**
	 * Show the tag on the frontend
	 *
	 * @param	column data
	 * @param	tag parameters
	 * @param	tag pair contents
	 * @return	parsed output
	 */
	public function replace_tag($data, $params = '', $tagdata = '')
	{
		if ($tagdata)
		{
			return $tagdata;
		}

		return $data;
	}

	// --------------------------------------------------------------------

	/**
	 * Display the settings page
	 *
	 * This basically just constructs a table of stuff. Pretty simple.
	 *
	 * @param	array of previously saved settings
	 * @return	string
	 */
	public function display_settings($data)
	{
		ee()->lang->loadfile('fieldtypes');
		ee()->load->library('Relationships_ft_cp');
		$util = ee()->relationships_ft_cp;

		$form = $this->_form();
		$form->populate($data);
		$values = $form->values();

		$settings = array(
			array(
				'title' => 'rel_ft_channels',
				'desc' => 'rel_ft_channels_desc',
				'fields' => array(
					'relationship_channels' => array(
						'type' => 'checkbox',
						'wrap' => TRUE,
						'choices' => $util->all_channels(),
						'value' => ($values['channels']) ?: '--'
					)
				)
			),
			array(
				'title' => 'rel_ft_include',
				'desc' => 'rel_ft_include_desc',
				'fields' => array(
					'relationship_expired' => array(
						'type' => 'checkbox',
						'scalar' => TRUE,
						'choices' => array(
							'1' => lang('rel_ft_include_expired')
						),
						'value' => $values['expired']
					),
					'relationship_future' => array(
						'type' => 'checkbox',
						'scalar' => TRUE,
						'choices' => array(
 							'1' => lang('rel_ft_include_future')
						),
						'value' => $values['future']
					)
				)
			),
			array(
				'title' => 'rel_ft_categories',
				'desc' => 'rel_ft_categories_desc',
				'fields' => array(
					'relationship_categories' => array(
						'type' => 'checkbox',
						'nested' => TRUE,
						'choices' => $util->all_categories(),
						'value' => ($values['categories']) ?: '--'
					)
				)
			),
			array(
				'title' => 'rel_ft_authors',
				'desc' => 'rel_ft_authors_desc',
				'fields' => array(
					'relationship_authors' => array(
						'type' => 'checkbox',
						'nested' => TRUE,
						'choices' => $util->all_authors(),
						'value' => ($values['authors']) ?: '--'
					)
				)
			),
			array(
				'title' => 'rel_ft_statuses',
				'desc' => 'rel_ft_statuses_desc',
				'fields' => array(
					'relationship_statuses' => array(
						'type' => 'checkbox',
						'wrap' => TRUE,
						'choices' => $util->all_statuses(),
						'value' => ($values['statuses']) ?: '--'
					)
				)
			),
			array(
				'title' => 'rel_ft_limit',
				'desc' => 'rel_ft_limit_desc',
				'fields' => array(
					'limit' => array(
						'type' => 'text',
						'value' => $values['limit']
					)
				)
			),
			array(
				'title' => 'rel_ft_order',
				'desc' => 'rel_ft_order_desc',
				'fields' => array(
					'relationship_order_field' => array(
						'type' => 'select',
						'choices' => array(
							'title' 	 => lang('rel_ft_order_title'),
							'entry_date' => lang('rel_ft_order_date')
						),
						'value' => $values['order_field']
					),
					'relationship_order_dir' => array(
						'type' => 'select',
						'choices' => array(
							'asc' => lang('rel_ft_order_ascending'),
							'desc'	=> lang('rel_ft_order_descending'),
						),
						'value' => $values['order_dir']
					)
				)
			),
			array(
				'title' => 'rel_ft_allow_multi',
				'desc' => 'rel_ft_allow_multi_desc',
				'fields' => array(
					'relationship_allow_multiple' => array(
						'type' => 'yes_no',
						'value' => ($values['allow_multiple']) ? 'y' : 'n'
					)
				)
			)
		);

		if ($this->content_type() == 'grid')
		{
			return array('field_options' => $settings);
		}

		return array('field_options_relationship' => array(
			'label' => 'field_options',
			'group' => 'relationship',
			'settings' => $settings
		));
	}

	// --------------------------------------------------------------------

	/**
	 * Save Settings
	 *
	 * Save the settings page. Populates the defaults, adds the user
	 * settings and sends that off to be serialized.
	 *
	 * @return	array	settings
	 */
	public function save_settings($data)
	{
		$form = $this->_form();
		$form->populate($data);

		$save = $form->values();

		// Boolstring conversion
		$save['allow_multiple'] = ($save['allow_multiple'] == 'y') ? 1 : 0;

		foreach ($save as $field => $value)
		{
			if (is_array($value) && count($value))
			{
				if (in_array('--', $value))
				{
					$save[$field] = array();
				}
			}
		}

		return $save;
	}

	// --------------------------------------------------------------------

	/**
	 * Setup the form helper
	 *
	 * Assigns blank data, default data, and all the form options.
	 *
	 * @param	form prefix
	 * @return	Object<Relationship_settings_form>
	 */
	protected function _form($prefix = 'relationship')
	{
		ee()->load->library('Relationships_ft_cp');
		$util = ee()->relationships_ft_cp;

		$field_empty_values = array(
			'channels'		=> '--',
			'expired'		=> 0,
			'future'		=> 0,
			'categories'	=> '--',
			'authors'		=> '--',
			'statuses'		=> '--',
			'limit'			=> 100,
			'order_field'	=> 'title',
			'order_dir'		=> 'asc',
			'allow_multiple'	=> 'n'
		);

		$field_options = array(
			'channels' 	  => $util->all_channels(),
			'categories'  => $util->all_categories(),
			'authors'	  => $util->all_authors(),
			'statuses'	  => $util->all_statuses(),
			'order_field' => $util->all_order_options(),
			'order_dir'	  => $util->all_order_directions()
		);

		// any default values that are not the empty ones
		$default_values = array(
			'allow_multiple' => 1
		);

		$form = $util->form($field_empty_values, $prefix);
		$form->options($field_options);
		$form->populate($default_values);

		return $form;
	}

	// --------------------------------------------------------------------

	/**
	 * Create our table on install
	 *
	 * @return	void
	 */
	public function install()
	{
		if (ee()->db->table_exists($this->_table))
		{
			return;
		}

		ee()->load->dbforge();

		$fields = array(
			'relationship_id' => array(
				'type'				=> 'int',
				'constraint'		=> 6,
				'unsigned'			=> TRUE,
				'auto_increment'	=> TRUE
			),
			'parent_id'	=> array(
				'type'				=> 'int',
				'constraint'		=> 10,
				'unsigned'			=> TRUE,
				'default'			=> 0
			),
			'child_id'  => array(
				'type'				=> 'int',
				'constraint'		=> 10,
				'unsigned'			=> TRUE,
				'default'			=> 0
			),
			'field_id'  => array(
				'type'				=> 'int',
				'constraint'		=> 10,
				'unsigned'			=> TRUE,
				'default'			=> 0
			),
			'order' 	=> array(
				'type'				=> 'int',
				'constraint'		=> 10,
				'unsigned'			=> TRUE,
				'default'			=> 0
			),
			'grid_field_id' => array(
				'type'			=> 'int',
				'constraint'	=> 10,
				'unsigned'		=> TRUE,
				'default'		=> 0,
				'null'			=> FALSE
			),
			'grid_col_id' => array(
				'type'			=> 'int',
				'constraint'	=> 10,
				'unsigned'		=> TRUE,
				'default'		=> 0,
				'null'			=> FALSE
			),
			'grid_row_id' => array(
				'type'			=> 'int',
				'constraint'	=> 10,
				'unsigned'		=> TRUE,
				'default'		=> 0,
				'null'			=> FALSE
			)
		);

		ee()->dbforge->add_field($fields);

		// Worthless primary key
		ee()->dbforge->add_key('relationship_id', TRUE);

		// Keyed table is keyed
		ee()->dbforge->add_key('parent_id');
		ee()->dbforge->add_key('child_id');
		ee()->dbforge->add_key('field_id');
		ee()->dbforge->add_key('grid_row_id');

		ee()->dbforge->create_table($this->_table);
	}

	// --------------------------------------------------------------------

	/**
	 * Drop the table
	 *
	 * @return	void
	 */
	public function uninstall()
	{
		ee()->load->dbforge();
		ee()->dbforge->drop_table($this->_table);
	}

	// --------------------------------------------------------------------

	/**
	 * Make sure that we only accept data for grid and channels.
	 *
	 * Long term this should support all content types, but currently that
	 * is not the case.
	 *
	 * @param string  The name of the content type
	 * @return bool    Allows content type?
	 */
	public function accepts_content_type($name)
	{
		return ($name == 'channel' || $name == 'grid');
	}

	// --------------------------------------------------------------------

	/**
	 * Modify column settings for a Relationship field in a grid.
	 *
	 * @param	array	$data	An array of data with the structure:
	 *								field_id - The id of the field to modify.
	 * 								ee_action - delete or add (action we're
	 *									taking)
	 *
	 * @return	array	The SQL definition of the modified field.
	 */
	public function grid_settings_modify_column($data)
	{
		return $this->_settings_modify_column($data, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Settings Modify Column
	 *
	 * @param	array
	 *		field_id
	 *		ee_action - delete OR add
	 * @return	array
	 */
	public function settings_modify_column($data)
	{
		return $this->_settings_modify_column($data);
	}

	/**
	 * Modify column settings for a Relationship field
	 *
	 * Handles cases both in and out of Grids, since they're mostly the
	 * same except for a minor tweak (col_id_ vs field_id_).  If you need
	 * to add something to both add it here.  Make sure it's valid for
	 * both and you account for the change in field name.
	 *
	 * @param	array	$data	An array of data with the structure:
	 *								field_id - The id of the field to modify.
	 * 								ee_action - delete or add (action we're
	 *									taking)
	 * @param	boolean	$grid	Are we working with a grid field? If TRUE, we
	 * 							are otherwise, it's a normal Relationship field.
	 *
	 * @return	array	The SQL definition of the modified field.
	 */
	protected function _settings_modify_column($data, $grid = FALSE)
	{
		if ($data['ee_action'] == 'delete')
		{
			$this->_clear_defunct_relationships(
				($grid) ? $data['col_id'] : $data['field_id'],
				$grid
			);
		}

		// pretty much a dummy field. Here just for consistency's sake
		// and in case we decide to store something in it.
		$field_name = ($grid ? 'col_id_' . $data['col_id'] : 'field_id_' . $data['field_id']);

		$fields[$field_name] = array(
			'type' => 'VARCHAR',
			'constraint' => 8
		);

		return $fields;
	}

	/**
	 * Delete the relationship rows belonging to a field that has been deleted.
	 * This code is called from multiple places.
	 *
	 * @param	int	$field_id	The id of the deleted field.
	 *
	 * @return void
	 */
	protected function _clear_defunct_relationships($field_id, $grid = FALSE)
	{
		// remove relationships
		if ($grid)
		{
			ee()->db
				->where('grid_col_id', $field_id)
				->delete($this->_table);
		}
		else
		{
			ee()->db
				->where('field_id', $field_id)
				->delete($this->_table);
		}
	}
}

// END Relationship_ft class

/* End of file ft.relationship.php */
/* Location: ./system/expressionengine/fieldtypes/ft.relationship.php */
