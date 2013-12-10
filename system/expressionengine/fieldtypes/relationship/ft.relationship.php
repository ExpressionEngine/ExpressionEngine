<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
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
	public function save($data)
	{
		$sort = isset($data['sort']) ? $data['sort'] : array();
		$data = isset($data['data']) ? $data['data'] : array();

		$sort = array_filter($sort);

		$cache_name = $this->field_name;

		if (isset($this->settings['grid_row_name']))
		{
			$cache_name .= $this->settings['grid_row_name'];
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
		$entry_id = $this->settings['entry_id'];

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

		$entry_id = ee()->input->get('entry_id');

		$order = array();
		$entries = array();
		$selected = array();

		if (is_array($data) && isset($data['data']) && ! empty($data['data'])) // autosave
		{
			foreach ($data['data'] as $k => $id)
			{
				$selected[$k] = $id;
				$order[$id] = isset($data['sort'][$k]) ? $data['sort'][$k] : 0;
			}
		}

		if ($entry_id)
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
				$selected[] = $row['child_id'];
				$order[$row['child_id']] = $row['order'];
			}
		}


		$limit_channels = $this->settings['channels'];
		$limit_categories = $this->settings['categories'];
		$limit_statuses = $this->settings['statuses'];
		$limit_authors = $this->settings['authors'];
		$limit = $this->settings['limit'];

		$show_expired = (bool) $this->settings['expired'];
		$show_future = (bool) $this->settings['future'];

		$order_field = $this->settings['order_field'];

		$separate_query_for_selected = (count($selected) && $limit);

		if ($separate_query_for_selected)
		{
			ee()->db->start_cache();
		}

		// Bug 19321, old fields use date
		if ($order_field == 'date')
		{
			$order_field = 'entry_date';
		}

		ee()->db
			->distinct()
			->from('channel_titles')
			->select('channel_titles.entry_id, channel_titles.title')
			->order_by($order_field, $this->settings['order_dir']);

		if (count($limit_channels))
		{
			ee()->db->where_in('channel_titles.channel_id', $limit_channels);
		}

		if (count($limit_categories))
		{
			ee()->db->from('category_posts');
			ee()->db->where('exp_channel_titles.entry_id = exp_category_posts.entry_id', NULL, FALSE); // todo ick
			ee()->db->where_in('category_posts.cat_id', $limit_categories);
		}

		if (count($limit_statuses))
		{
			$limit_statuses = str_replace(
				array('Open', 'Closed'),
				array('open', 'closed'),
				$limit_statuses
			);

			ee()->db->where_in('channel_titles.status', $limit_statuses);
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

			$where = '';

			if (count($members))
			{
				$where .= ee()->db->dbprefix('channel_titles').'.author_id IN ('.implode(', ', $members).')';
			}

			if (count($groups))
			{
				$where .= $where ? ' OR ' : '';
				$where .= ee()->db->dbprefix('members').'.group_id IN ('.implode(', ', $groups).')';
				ee()->db->join('members', 'members.member_id = channel_titles.author_id');
			}

			if ($where)
			{
				ee()->db->where("({$where})");
			}
		}

		// Limit times
		$now = ee()->localize->now;

		if ( ! $show_future)
		{
			ee()->db->where('channel_titles.entry_date < ', $now);
		}

		if ( ! $show_expired)
		{
			$t = ee()->db->dbprefix('channel_titles');
			ee()->db->where("(${t}.expiration_date = 0 OR ${t}.expiration_date > ${now})", NULL, FALSE);
		}

		if ($entry_id)
		{
			ee()->db->where('channel_titles.entry_id !=', $entry_id);
		}

		if ($limit)
		{
			ee()->db->limit($limit);
		}

		// If we've got a limit and selected entries, we need to run the query
		// twice. Once without those entries and then separately with only those
		// entries.

		if ($separate_query_for_selected)
		{
			ee()->db->stop_cache();
			ee()->db->where_not_in('channel_titles.entry_id', $selected);
		}

		$entries = ee()->db->get()->result_array();

		if ($separate_query_for_selected)
		{
			ee()->db->limit(count($selected));
			ee()->db->where_in('channel_titles.entry_id', $selected);
			$entries = array_merge(
				$entries,
				ee()->db->get()->result_array()
			);
		}

		ee()->db->flush_cache();

		if ($this->settings['allow_multiple'] == 0)
		{
			$options[''] = '--';

			foreach ($entries as $entry)
			{
				$options[$entry['entry_id']] = $entry['title'];
			}

			return form_dropdown($field_name.'[data][]', $options, current($selected));
		}

		ee()->cp->add_js_script(array(
			'plugin' => 'ee_interact.event',
			'file' => 'cp/relationships',
			'ui' => 'sortable'
		));

		if ( ! isset($this->settings['grid_row_id']) && substr($field_name, 7) != 'col_id_' && count($entries))
		{
			ee()->javascript->output("EE.setup_relationship_field('".$this->field_name."');");
		}

		if (REQ == 'CP')
		{
			$css_link = ee()->view->head_link('css/relationship.css');
		}
		// Channel Form
		else
		{
			$css_link = '<link rel="stylesheet" href="'.ee()->config->slash_item('theme_folder_url').'cp_themes/default/css/relationship.css" type="text/css" media="screen" />'.PHP_EOL;
		}

		ee()->cp->add_to_head($css_link);

		return ee()->load->view('publish', compact('field_name', 'entries', 'selected', 'order'), TRUE);
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

		$form = $this->_form();
		$form->populate($data);

		ee()->table->set_heading(array(
			'data' => lang('rel_ft_options'),
			'colspan' => 2
		));

		$this->_row(
			'<strong>'.lang('rel_ft_configure').'</strong><br><i class="instruction_text">'.lang('rel_ft_configure_subtext').'</i>'
		);

		$this->_row(
			lang('rel_ft_channels'),
			$form->multiselect('channels', 'style="min-width: 225px; height: 140px;"'),
			'top'
		);

		$this->_row(
			lang('rel_ft_include'),
			'<label>'.$form->checkbox('expired').' '.lang('rel_ft_include_expired').'</label>'.
				NBS.NBS.' <label>'.$form->checkbox('future').' '.lang('rel_ft_include_future').'</label>'
		);
		$this->_row(
			lang('rel_ft_categories'),
			$form->multiselect('categories', 'style="min-width: 225px; height: 140px;"'),
			'top'
		);

		$this->_row(
			lang('rel_ft_authors'),
			$form->multiselect('authors', 'style="min-width: 225px; height: 57px;"'),
			'top'
		);
		$this->_row(
			lang('rel_ft_statuses'),
			$form->multiselect('statuses', 'style="min-width: 225px; height: 43px;"'),
			'top'
		);
		$this->_row(
			lang('rel_ft_limit_left'),
			$form->input('limit', 'class="center" style="width: 55px;"').NBS.
				' <strong>'.lang('rel_ft_limit_right').'</strong> <i class="instruction_text">('.lang('rel_ft_limit_subtext').')</i>'
		);
		$this->_row(
			lang('rel_ft_order'),
			$form->dropdown('order_field').' &nbsp; <strong>'.lang('rel_ft_order_in').'</strong> &nbsp; '.$form->dropdown('order_dir')
		);
		$this->_row(
			lang('rel_ft_allow_multi'),
			'<label>'.$form->checkbox('allow_multiple').' '.lang('yes').' </label> <i class="instruction_text">('.lang('rel_ft_allow_multi_subtext').')</i>'
		);

		return ee()->table->generate();
	}

	// --------------------------------------------------------------------

	public function grid_display_settings($data)
	{
		ee()->load->library('Relationships_ft_cp');
		$util = ee()->relationships_ft_cp;

		return array(
			$this->grid_checkbox_row(
				lang('rel_ft_include_expired'),
				'expired',
				1,
				(isset($data['expired']) && $data['expired'] == 1)
			),
			$this->grid_checkbox_row(
				lang('rel_ft_include_future'),
				'future',
				1,
				(isset($data['future']) && $data['future'] == 1)
			),
			$this->grid_dropdown_row(
				lang('channels'),
				'channels[]',
				$util->all_channels(),
				isset($data['channels']) ? $data['channels'] : NULL,
				TRUE, // Multiselect
				TRUE, // Wide select box
				'style="height: 140px"'
			),
			$this->grid_dropdown_row(
				lang('categories'),
				'categories[]',
				$util->all_categories(),
				isset($data['categories']) ? $data['categories'] : NULL,
				TRUE,
				TRUE,
				'style="height: 140px"'
			),
			$this->grid_dropdown_row(
				lang('rel_ft_authors'),
				'authors[]',
				$util->all_authors(),
				isset($data['authors']) ? $data['authors'] : NULL,
				TRUE,
				TRUE,
				'style="height: 57px"'
			),
			$this->grid_dropdown_row(
				lang('statuses'),
				'statuses[]',
				$util->all_statuses(),
				isset($data['statuses']) ? $data['statuses'] : NULL,
				TRUE,
				TRUE,
				'style="height: 43px"'
			),
			form_label(lang('grid_show')).NBS.NBS.NBS.
			form_input(array(
				'name'	=> 'limit',
				'size'	=> 4,
				'value'	=> isset($data['limit']) ? $data['limit'] : 100,
				'class'	=> 'grid_input_text_small'
			)).NBS.NBS.NBS.
			form_label(lang('entries')),

			// Order by row
			form_label(lang('grid_order_by')).NBS.NBS.
			form_dropdown(
				'order_field',
				$util->all_order_options(),
				isset($data['order_field']) ? $data['order_field'] : NULL
			).NBS.NBS.
			form_label(lang('in')).NBS.NBS.
			form_dropdown(
				'order_dir',
				$util->all_order_directions(),
				isset($data['order_dir']) ? $data['order_dir'] : NULL
			),

			// Allow multiple
			$this->grid_checkbox_row(
				lang('rel_ft_allow_multi'),
				'allow_multiple',
				1,
				(isset($data['allow_multiple']) && $data['allow_multiple'] == 1)
			)
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Table row helper
	 *
	 * Help simplify the form building and enforces a strict layout. If
	 * you think this table needs to look different, go bug James.
	 *
	 * @param	left cell content
	 * @param	right cell content
	 * @param	vertical alignment of left column
	 *
	 * @return	void - adds a row to the EE table class
	 */
	protected function _row($cell1, $cell2 = '', $valign = 'center')
	{
		if ( ! $cell2)
		{
			ee()->table->add_row(
				array('data' => $cell1, 'colspan' => 2)
			);
		}
		else
		{
			ee()->table->add_row(
				array('data' => '<strong>'.$cell1.'</strong>', 'width' => '170px', 'valign' => $valign),
				array('data' => $cell2, 'class' => 'id')
			);
		}
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
			'channels'		=> array(),
			'expired'		=> 0,
			'future'		=> 0,
			'categories'	=> array(),
			'authors'		=> array(),
			'statuses'		=> array(),
			'limit'			=> 100,
			'order_field'	=> 'title',
			'order_dir'		=> 'asc',
			'allow_multiple'	=> 0
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

		$field['field_id_'.$data['field_id']] = array(
			'type' 			=> 'INT',
			'constraint'	=> 10,
			'null' 			=> FALSE,
			'default'		=> 0
			);

		return $field;
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
	protected function _settings_modify_column($data, $grid=FALSE)
	{
		if ($data['ee_action'] == 'delete')
		{
			$this->_clear_defunct_relationships($data['field_id']);
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
	protected function _clear_defunct_relationships($field_id)
	{
		// remove relationships
		ee()->db
			->where('field_id', $field_id)
			->delete($this->_table);
	}
}

// END Relationship_ft class

/* End of file ft.relationship.php */
/* Location: ./system/expressionengine/fieldtypes/ft.relationship.php */
