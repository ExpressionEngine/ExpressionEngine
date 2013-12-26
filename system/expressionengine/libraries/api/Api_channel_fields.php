<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Api_channel_fields extends Api {

	var $custom_fields		= array();
	var $field_types		= array();
	var $ft_paths			= array();
	var $settings			= array();
	var $native				= array();

	var $ee_base_ft			= FALSE;
	var $global_settings;

	public function __construct()
	{
		parent::__construct();

		$this->native = array(
			'field_id', 'site_id', 'group_id',
			'field_name', 'field_label', 'field_instructions',
			'field_type', 'field_list_items', 'field_pre_populate',
			'field_pre_channel_id', 'field_pre_field_id',
			'field_ta_rows', 'field_maxl', 'field_required',
			'field_text_direction', 'field_search', 'field_is_hidden', 'field_fmt', 'field_show_fmt',
			'field_order'
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Set settings
	 *
	 * @access	public
	 */
	function set_settings($field_id, $settings)
	{
		if ( ! array_key_exists('field_name', $settings))
		{
			$settings['field_name'] = $field_id;
		}

		if ( ! array_key_exists($settings['field_type'], $this->field_types))
		{
			$this->field_types[$settings['field_type']] = $this->include_handler($settings['field_type']);
		}

		$this->settings[$field_id] = $settings;
	}

	// --------------------------------------------------------------------

	/**
	 * Get settings
	 *
	 * @access	public
	 */
	function get_settings($field_id)
	{
		return isset($this->settings[$field_id]) ? $this->settings[$field_id] : array();
	}

	// --------------------------------------------------------------------

	/**
	 * Get global settings
	 *
	 * @access	public
	 */
	function get_global_settings($field_type)
	{
		if ( ! $this->global_settings)
		{
			$this->global_settings = array();
			$this->fetch_installed_fieldtypes();
		}

		if (isset($this->global_settings[$field_type]))
		{
			return $this->global_settings[$field_type];
		}

		return array();
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch all fieldtypes
	 *
	 * @access	public
	 */
	function fetch_all_fieldtypes()
	{
		return $this->_fetch_fts('get_files');
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch defined custom fields
	 *
	 * @access	public
	 */
	function fetch_installed_fieldtypes()
	{
		return $this->_fetch_fts('get_installed');
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch fieldtypes
	 *
	 * Convenience method to reduce code duplication
	 *
	 * @access	private
	 */
	function _fetch_fts($method)
	{
		ee()->load->library('addons');
		$fts = ee()->addons->$method('fieldtypes');

		foreach($fts as $key => $data)
		{
			$this->field_types[$key] = $this->include_handler($key);

			if (isset($data['settings']))
			{
				$this->global_settings[$key] = unserialize(base64_decode($data['settings']));
			}

			$opts = get_class_vars($data['class']);
			$fts[$key] = array_merge($fts[$key], $opts['info']);
		}

		return $fts;
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch defined custom fields
	 *
	 * @access	public
	 */
	function fetch_custom_channel_fields()
	{
		ee()->db->select('field_id, field_type, field_fmt, field_name, site_id, field_settings');
		$query = ee()->db->get('channel_fields');

		$cfields = array();
		$dfields = array();
		$rfields = array();
		$gfields = array();
		$pfields = array();

		foreach ($query->result_array() as $row)
		{
			if ( ! array_key_exists($row['field_type'], $this->field_types))
			{
				$this->field_types[$row['field_type']] = $this->include_handler($row['field_type']);
			}

			$this->custom_fields[$row['field_id']] = $row['field_type'];

			if ($row['field_type'] == 'date')
			{
				$dfields[$row['site_id']][$row['field_name']] = $row['field_id'];
			}
			elseif ($row['field_type'] == 'relationship')
			{
				$rfields[$row['site_id']][$row['field_name']] = $row['field_id'];
			}
			else
			{
				$field_handler = $this->field_types[$row['field_type']];
				$field_handler = is_object($field_handler) ? get_class($field_handler) : $field_handler;

				// Yay for PHP 4
				$class_vars = get_class_vars($field_handler);

				if (isset($class_vars['has_array_data']) && $class_vars['has_array_data'] === TRUE)
				{
					$pfields[$row['site_id']][$row['field_id']] = $row['field_type'];
				}
			}

			if (isset($row['field_settings']) && $row['field_settings'] != '')
			{
				$settings = unserialize(base64_decode($row['field_settings']));
				$settings['field_type'] = $row['field_type'];
				$settings['field_fmt'] = $row['field_fmt'];

				$this->set_settings($row['field_id'], $settings);
			}

			if ($row['field_type'] == 'grid')
			{
				$gfields[$row['site_id']][$row['field_name']] = $row['field_id'];
			}


			$cfields[$row['site_id']][$row['field_name']] = $row['field_id'];
		}

		return array(
			'custom_channel_fields'	=> $cfields,
			'date_fields'			=> $dfields,
			'relationship_fields'	=> $rfields,
			'grid_fields'			=> $gfields,
			'pair_custom_fields'	=> $pfields
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Include a custom field handler
	 *
	 * @access	public
	 */
	function include_handler($field_type)
	{
		if ( ! $this->ee_base_ft)
		{
			require_once APPPATH.'fieldtypes/EE_Fieldtype.php';
			$this->ee_base_ft = TRUE;
		}

		if ( ! isset($this->field_types[$field_type]))
		{
			$file = 'ft.'.$field_type.'.php';
			$paths = array(PATH_FT.$field_type.'/');

			ee()->load->library('addons');

			$fts = ee()->addons->get_files('fieldtypes');

			if (isset($fts[$field_type]))
			{
				$paths[] = PATH_THIRD.$fts[$field_type]['package'].'/';
			}

			$paths[] = PATH_MOD.$field_type.'/';

			$found_path = FALSE;

			foreach ($paths as $path)
			{
				if (file_exists($path.$file))
				{
					$found_path = TRUE;

					break;
				}
			}

			if ( ! $found_path)
			{
				show_error(sprintf(ee()->lang->line('unable_to_load_field_type'),
										strtolower($file)));
			}

			require_once $path.$file;

			$this->ft_paths[$field_type] = $path;
			$this->field_types[$field_type] = ucfirst($field_type.'_ft');
		}

		return $this->field_types[$field_type];
	}

	// --------------------------------------------------------------------

	/**
	 * Setup or re-initialize field type handler
	 *
	 * @access	public
	 */
	function setup_handler($field_type, $return_obj = FALSE)
	{
		$field_id = FALSE;
		$frontend = FALSE;

		// Quite frequently all you have convenient access to
		// is a field_id. We can do a lookup based on some of the
		// other data we have.

		if (isset($this->custom_fields[$field_type]))
		{
			$frontend = TRUE;
			$field_id = $field_type;
			$field_type = $this->custom_fields[$field_type];
		}
		elseif (isset($this->settings[$field_type]))
		{
			$field_id = $field_type;
			$field_type = $this->settings[$field_id]['field_type'];
		}

		// Now that we know that we're definitely working
		// with a field_type name. Look for it.
		if ( ! isset($this->field_types[$field_type]))
		{
			return FALSE;
		}

		// Instantiate it if we haven't used it yet.
		if ( ! is_object($this->field_types[$field_type]))
		{
			$this->include_handler($field_type);

			$this->field_types[$field_type] =& $this->_instantiate_handler($field_type);
		}

		// If we started with a field_id, but we're not on the frontend
		// (which means fetch_custom_channel_fields didn't get called),
		// we need to make sure we have the proper field name.

		$field_name = FALSE;

		$settings = $this->get_settings($field_id);

		if (isset($settings['field_name']))
		{
			$field_name	= $settings['field_name'];
		}

		// Merge field settings with the global settings
		$settings = array_merge($this->get_global_settings($field_type), $settings);

		// Initialize fieldtype with settings for this field
		$this->field_types[$field_type]->_init(array(
			'settings'		=> $settings,
			'field_id'		=> $field_id,
			'field_name'	=> $field_name
		));

		// Remember what we set up so that apply
		// calls go to the right spot
		$this->field_type = $field_type;

		return ($return_obj) ? $this->field_types[$field_type] : TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Instantiate a fieldtype handler
	 *
	 * Normally this method does not need to be called, it's here for
	 * convenience. Use setup_handler() unless you're 100% sure you
	 * need this.
	 *
	 * @access	private
	 */
	function &_instantiate_handler($field_type)
	{
		$class		= $this->field_types[$field_type];
		$_ft_path	= $this->ft_paths[$field_type];

		ee()->load->add_package_path($_ft_path, FALSE);

		$obj = new $class();

		ee()->load->remove_package_path();

		return $obj;
	}

	// --------------------------------------------------------------------

	/**
	 * Route the call to the proper handler
	 *
	 * Doing it this way so we don't have to pass objects around with PHP 4
	 * being annoying as it is.
	 *
	 * Using the name of the identical javascript function, and yes, I like it.
	 *
	 * @access	public
	 */
	function apply($method, $parameters = array())
	{
		$_ft_path = $this->ft_paths[$this->field_type];

		ee()->load->add_package_path($_ft_path, FALSE);

		$ft =& $this->field_types[$this->field_type];

		if (count($parameters))
		{
			$parameters = $this->custom_field_data_hook($ft, $method, $parameters);
		}

		$res = call_user_func_array(array(&$ft, $method), $parameters);

		ee()->load->remove_package_path($_ft_path);

		return $res;
	}

	// --------------------------------------------------------------------

	/**
	 * Checks for the method
	 *
	 *
	 * Used as a conditional before calling apply in some modules
	 *
	 * @access	public
	 */
	function check_method_exists($method)
	{
		$field_type = &$this->field_types[$this->field_type];

		if (method_exists($field_type, $method))
		{
			return TRUE;
		}

		return FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Adds new custom field table fields
	 *
	 *
	 * Add new fields to channel_data on custom field creation
	 *
	 * @access	public
	 * @param	array
	 * @return	void
	 */
	function add_datatype($field_id, $data)
	{
		$this->set_datatype($field_id, $data, array(), TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Delete custom field table fields
	 *
	 *
	 * Deletes fields from channel_data on custom field deletion
	 *
	 * @access	public
	 * @param	array
	 * @return	void
	 */
	function delete_datatype($field_id, $data, $overrides = array())
	{
		$defaults = array(
			'id_field'				=> 'field_id',
			'col_settings_method'	=> 'settings_modify_column',
			'col_prefix'			=> 'field',
			'data_table'			=> 'channel_data'
		);

		foreach ($overrides as $key => $value)
		{
			$defaults[$key] = $value;
		}

		extract($defaults);

		$id_field_name = $col_prefix.'_id_'.$field_id;
		$ft_field_name = $col_prefix.'_ft_'.$field_id;

		// merge in a few variables to the data array
		$data[$id_field] = $field_id;
		$data['ee_action'] = 'delete';

		$fields = $this->apply($col_settings_method, array($data));

		if ( ! isset($fields[$id_field_name]))
		{
			$fields[$id_field_name] = '';
		}

		if ( ! isset($fields[$ft_field_name]) && $id_field != 'col_id')
		{
			$fields[$ft_field_name] = '';
		}

		ee()->load->dbforge();
		$delete_fields = array_keys($fields);

		foreach ($delete_fields as $col)
		{
			ee()->dbforge->drop_column($data_table, $col);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Edit custom field table fields
	 *
	 *
	 * Compares old field data to new field data and adds/modifies exp_channel_data
	 * fields as needed
	 *
	 * @access	public
	 * @param	mixed (field_id)
	 * @param	string (field_type)
	 * @param	array
	 * @return	void
	 */
	function edit_datatype($field_id, $field_type, $data, $overrides = array())
	{
		$defaults = array(
			'id_field'				=> 'field_id',
			'type_field'			=> 'field_type',
			'col_settings_method'	=> 'settings_modify_column',
			'col_prefix'			=> 'field',
			'fields_table'			=> 'channel_fields',
			'data_table'			=> 'channel_data'
		);

		foreach ($overrides as $key => $value)
		{
			$defaults[$key] = $value;
		}

		extract($defaults);

		$id_field_name = $col_prefix.'_id_'.$field_id;
		$ft_field_name = $col_prefix.'_ft_'.$field_id;

		$old_fields = array();

		// First we get the data
		$query = ee()->db->get_where($fields_table, array($id_field => $field_id));

		$this->setup_handler($query->row($type_field));

		// Field type changed ?
		$type = ($query->row($type_field) == $field_type) ? 'get_data' : 'delete';

		$old_data = $query->row_array();

		// merge in a few variables to the data array
		$old_data[$id_field] = $field_id;
		$old_data['ee_action'] = $type;

		$old_fields = $this->apply($col_settings_method, array($old_data));

		// Switch handler back to the new field type
		$this->setup_handler($field_type);

		if ( ! isset($old_fields[$id_field_name]))
		{
			$old_fields[$id_field_name]['type'] = 'text';
			$old_fields[$id_field_name]['null'] = TRUE;
		}

		if ( ! isset($old_fields[$ft_field_name]) && $id_field != 'col_id')
		{
			$old_fields[$ft_field_name]['type'] = 'tinytext';
			$old_fields[$ft_field_name]['null'] = TRUE;
		}

		// Delete extra fields
		if ($type == 'delete')
		{
			ee()->load->dbforge();
			$delete_fields = array_keys($old_fields);

			foreach ($delete_fields as $col)
			{
				if ($col == $id_field_name OR $col == $ft_field_name)
				{
					continue;
				}

				ee()->dbforge->drop_column($data_table, $col);
			}

		}

		$type_change = ($type == 'delete') ? TRUE : FALSE;

		$this->set_datatype($field_id, $data, $old_fields, FALSE, $type_change, $overrides);
	}

	// --------------------------------------------------------------------

	/**
	 * Set data type
	 *
	 *
	 * Used primarily by add_datatype and edit_datatype to do the actual table manipulation
	 * when a custom field is added or edited
	 *
	 * @access	public
	 * @param	mixed (field_id)
	 * @param	array (new custom field data)
	 * @param	array (old custom field data)
	 * @param	bool (TRUE if it is a new field)
	 * @param	bool (TRUE if the field type changed)
	 * @return	void
	 */
	function set_datatype($field_id, $data, $old_fields = array(), $new = TRUE, $type_change = FALSE, $overrides = array())
	{
		$defaults = array(
			'id_field'				=> 'field_id',
			'col_settings_method'	=> 'settings_modify_column',
			'col_prefix'			=> 'field',
			'data_table'			=> 'channel_data'
		);

		foreach ($overrides as $key => $value)
		{
			$defaults[$key] = $value;
		}

		extract($defaults);

		$id_field_name = $col_prefix.'_id_'.$field_id;
		$ft_field_name = $col_prefix.'_ft_'.$field_id;

		ee()->load->dbforge();

		// merge in a few variables to the data array
		$data[$id_field] = $field_id;
		$data['ee_action'] = 'add';

		// We have to get the new fields regardless to check whether they were modified
		$fields = $this->apply($col_settings_method, array($data));

		if ( ! isset($fields[$id_field_name]))
		{
			$fields[$id_field_name]['type'] = 'text';
			$fields[$id_field_name]['null'] = TRUE;
		}

		if ( ! isset($fields[$col_prefix.'_ft_'.$field_id]) && $id_field != 'col_id')
		{
			$fields[$ft_field_name]['type'] = 'tinytext';
			$fields[$ft_field_name]['null'] = TRUE;
		}

		// Do we need to modify the field_id
		$modify = FALSE;

		if ( ! $new)
		{
			$diff1 = array_diff_assoc($old_fields[$id_field_name], $fields[$id_field_name]);
			$diff2 = array_diff_assoc($fields[$id_field_name], $old_fields[$id_field_name]);

			if ( ! empty($diff1) OR ! empty($diff2))
			{
				$modify = TRUE;
			}
		}

		// Add any new fields
		if ($type_change == TRUE OR $new == TRUE)
		{
			foreach ($fields as $field => $prefs)
			{
				if ( ! $new)
				{
					if ($field == $id_field_name OR $field == $ft_field_name)
					{
						continue;
					}
				}

				ee()->dbforge->add_column($data_table, array($field => $prefs));

				// Make sure the value is an empty string
				ee()->db->update(
					$data_table,
					array(
						$field => (isset($prefs['default'])) ? $prefs['default'] : ''
					)
				);
			}
		}

		// And modify any necessary fields
		if ($modify == TRUE)
		{
			$mod[$id_field_name] = $fields[$id_field_name];
			$mod[$id_field_name]['name'] = $id_field_name;

			ee()->dbforge->modify_column($data_table, $mod);
		}
	}


	// --------------------------------------------------------------------

	/**
	 * Get custom field info from modules
	 *
	 * @access	public
	 */
	function get_required_fields($channel_id)
	{
		ee()->load->model('channel_model');
		$required = array('title', 'entry_date');

		$query = ee()->channel_model->get_channel_info($channel_id, array('field_group'));

		if ($query->num_rows() > 0)
		{
			$row = $query->row();
			$fields = ee()->channel_model->get_required_fields($row->field_group);

			if ($fields->num_rows() > 0)
			{
				foreach ($fields->result() as $row)
				{
					$required[] = $row->field_id;
				}
			}
		}

		$module_data = $this->get_module_fields($channel_id);

		if ($module_data && is_array($module_data))
		{
			foreach ($module_data as $tab => $v)
			{
				foreach ($v as $val)
				{
					if ($val['field_required'] == 'y')
					{
						$required[] =  $val['field_id'];
					}
				}
			}
		}

		return $required;

	}

	// --------------------------------------------------------------------

	/**
	 * Get custom field info from modules
	 *
	 * @access	public
	 */
	function get_module_fields($channel_id, $entry_id = '')
	{
		$tab_modules = $this->get_modules();

		$set = FALSE;

		if ($tab_modules == FALSE)
		{
			return FALSE;
		}

		foreach ($tab_modules as $name)
		{
			$directory	= strtolower($name);
			$class_name	= ucfirst($directory).'_tab';

			$mod_base_path = $this->_include_tab_file($directory);

			ee()->load->add_package_path($mod_base_path, FALSE);

			$OBJ = new $class_name();

			if (method_exists($OBJ, 'publish_tabs') === TRUE)
			{
				// fetch the content
				$fields = $OBJ->publish_tabs($channel_id, $entry_id);

				// There's basically no way this *won't* be set, but let's check it anyhow.
				// When we find it, we'll append the module's classname to it to prevent
				// collission with other modules with similarly named fields. This namespacing
				// gets stripped as needed when the module data is processed in get_module_methods()
				// This function is called for insertion and editing of entries.
				// @php4 would be nice to use a reference in this foreach...

				foreach ($fields as $key => $field)
				{
					if (isset($field['field_id']))
					{
						$fields[$key]['field_id'] = $name.'__'.$field['field_id']; // two underscores
					}
				}

				$set[$name] = $fields;
			}

		// restore our package and view paths
		ee()->load->remove_package_path($mod_base_path);

		}

		return $set;
	}


	// --------------------------------------------------------------------

	/**
	 * Get custom field info from modules
	 *
	 * @access	public
	 */
	function get_module_methods($methods, $params = array())
	{
		$tab_modules = $this->get_modules();

		$set = FALSE;

		if ($tab_modules == FALSE)
		{
			return FALSE;
		}

		if ( ! is_array($methods))
		{
			$methods = array($methods);
		}

		foreach ($tab_modules as $name)
		{
			$directory	= strtolower($name);
			$class_name	= ucfirst($directory).'_tab';

			$mod_base_path = $this->_include_tab_file($directory);

			ee()->load->add_package_path($mod_base_path, FALSE);

			$OBJ = new $class_name();

			foreach ($methods as $method)
			{
				// if this data is getting inserted into the database, then we need to ensure we've
				// removed the automagically added classname from the field names
				if (isset($params['publish_data_db']['mod_data']))
				{
					$params['publish_data_db']['mod_data'] = $this->_clean_module_names($params['publish_data_db']['mod_data'], array($name));
				}
				elseif (isset($params['validate_publish'][0]))
				{
					$params['validate_publish'][0] = $this->_clean_module_names($params['validate_publish'][0], array($name));
				}

				if (method_exists($OBJ, $method) === TRUE)
				{
					if ( ! isset($params[$method]))
					{
						$params[$method] = '';
					}

					// fetch the content
					if ($method == 'publish_tabs')
					{
						$channel_id = $params['publish_tabs'][0];
						$entry_id = $params['publish_tabs'][1];

						// fetch the content
						$fields = $OBJ->publish_tabs($channel_id, $entry_id);

						$set[$name]['publish_tabs'] = $fields;
					}
					else
					{
						$set[$name][$method] = $OBJ->$method($params[$method]);
					}
				}
			}

			// restore our package and view paths
			ee()->load->remove_package_path($mod_base_path);

		}

		return $set;
	}

	// --------------------------------------------------------------------

	/**
	 * Include Tab File
	 *
	 * Loads the tab if it hasn't been used and returns the base path that
	 * can then be used to add the correct package paths.
	 *
	 * @access	public
	 */
	function _include_tab_file($name)
	{
		static $paths = array();

		// Have we encountered this one before?
		if ( ! isset($paths[$name]))
		{
			$class_name = ucfirst($name).'_tab';

			// First or third party?
			foreach(array(APPPATH.'modules/', PATH_THIRD) as $tmp_path)
			{
				if (file_exists($tmp_path.$name.'/tab.'.$name.'.php'))
				{
					$paths[$name] = $tmp_path.$name.'/';
					break;
				}
			}

			// Include file
			if ( ! class_exists($class_name))
			{
				if ( ! isset($paths[$name]))
				{
					show_error(sprintf(ee()->lang->line('unable_to_load_tab'), 'tab.'.$name.'.php'));
				}

				include_once($paths[$name].'tab.'.$name.'.php');
			}
		}

		return $paths[$name];
	}

	// --------------------------------------------------------------------

	/**
	 * Clean Module Names
	 *
	 * This function removes the automatically added module classname from its field inputs
	 *
	 * @access	public
	 */
	function _clean_module_names($array_to_clean = array(), $module_names = array())
	{
		if (empty($module_names))
		{
			return $array_to_clean;
		}

		if (isset($array_to_clean['revision_post']))
		{
			$array_to_clean['revision_post'] = $this->_clean_module_names($array_to_clean['revision_post'], $module_names);
		}

		foreach ($array_to_clean as $field => $value)
		{
			// loop through each module name
			foreach($module_names as $module_name)
			{
				$module_name.="__";

				if (strncmp($field, $module_name, strlen($module_name)) == 0)
				{
					// new name
					$cleared_field_name = str_replace($module_name, '', $field); // avoid passing the entire $module_names array for swapping to avoid common naming situations

					// unset old
					unset($array_to_clean[$field]);
					//reset new
					$array_to_clean[$cleared_field_name] = $value;
				}
			}
		}

		return $array_to_clean;
	}

	// --------------------------------------------------------------------

	function get_modules()
	{
		// Do we have modules in play
		ee()->load->model('addons_model');
		$custom_field_modules = FALSE;

		$mquery = ee()->addons_model->get_installed_modules(FALSE, TRUE);

		if ($mquery->num_rows() > 0)
		{
			foreach($mquery->result_array() as $row)
			{
				$custom_field_modules[] = $row['module_name'];
			}
		}

		return $custom_field_modules;
	}

	// --------------------------------------------------------------------

	function setup_entry_settings($channel_id, $entry_data, $bookmarklet = FALSE)
	{
		// Let's grab our channel data- note should be cached if already called via api
		ee()->api->instantiate('channel_structure');

		$channel_query = ee()->api_channel_structure->get_channel_info($channel_id);

		if ($channel_query->num_rows() == 0)
		{
			// bad return false?
		}

		$channel_data = $channel_query->row_array();


		// We start by setting our default fields

		$title = (isset($entry_data['title'])) ? $entry_data['title'] : '';

		if ($channel_data['default_entry_title'] != '' && $title == '')
		{
			$title = $channel_data['default_entry_title'];
		}


		$url_title = (isset($entry_data['url_title'])) ? $entry_data['url_title'] : '';


		$deft_fields = array(
			'title' 		=> array(
				'field_id'				=> 'title',
				'field_label'			=> lang('title'),
				'field_required'		=> 'y',
				'field_data'			=> $title,
				'field_show_fmt'		=> 'n',
				'field_instructions'	=> '',
				'field_text_direction'	=> 'ltr',
				'field_type'			=> 'text',
				'field_maxl'			=> 100
			),
			'url_title'		=> array(
				'field_id'				=> 'url_title',
				'field_label'			=> lang('url_title'),
				'field_required'		=> 'n',
				'field_data'			=> $url_title,
				'field_fmt'				=> 'xhtml',
				'field_instructions'	=> '',
				'field_show_fmt'		=> 'n',
				'field_text_direction'	=> 'ltr',
				'field_type'			=> 'text',
				'field_maxl'			=> 75
			),
			'entry_date'	=> array(
				'field_id'				=> 'entry_date',
				'field_label'			=> lang('entry_date'),
				'field_required'		=> 'y',
				'field_type'			=> 'date',
				'field_text_direction'	=> 'ltr',
				'field_data'			=> (isset($entry_data['entry_date'])) ? $entry_data['entry_date'] : '',
				'field_fmt'				=> 'text',
				'field_instructions'	=> '',
				'field_show_fmt'		=> 'n',
				'always_show_date'		=> 'y',
				'default_offset'		=> 0,
				'selected'				=> 'y',
			),
			'expiration_date' => array(
				'field_id'				=> 'expiration_date',
				'field_label'			=> lang('expiration_date'),
				'field_required'		=> 'n',
				'field_type'			=> 'date',
				'field_text_direction'	=> 'ltr',
				'field_data'			=> (isset($entry_data['expiration_date'])) ? $entry_data['expiration_date'] : '',
				'field_fmt'				=> 'text',
				'field_instructions'	=> '',
				'field_show_fmt'		=> 'n',
				'default_offset'		=> 0,
				'selected'				=> 'y',
			)
		);


		// comment expiry here.
		if (isset(ee()->cp->installed_modules['comment']))
		{
			$deft_fields['comment_expiration_date'] = array(
				'field_id'				=> 'comment_expiration_date',
				'field_label'			=> lang('comment_expiration_date'),
				'field_required'		=> 'n',
				'field_type'			=> 'date',
				'field_text_direction'	=> 'ltr',
				'field_data'			=> (isset($entry_data['comment_expiration_date'])) ? $entry_data['comment_expiration_date'] : '',
				'field_fmt'				=> 'text',
				'field_instructions'	=> '',
				'field_show_fmt'		=> 'n',
				'default_offset'		=> $channel_data['comment_expiration'] * 86400,
				'selected'				=> 'y',
			);
		}

		foreach ($deft_fields as $field_name => $f_data)
		{
			$this->set_settings($field_name, $f_data);
		}


		// Now we set our custom fields

		// Get Channel fields in the field group
		$channel_fields = ee()->channel_model->get_channel_fields($channel_data['field_group']);



		$field_settings = array();

		foreach ($channel_fields->result_array() as $row)
		{
			$field_fmt 		= $row['field_fmt'];
			$field_dt 		= '';
			$field_data		= '';

			if ($bookmarklet)
			{
				// Bookmarklet data perhaps?
				if (($field_data = ee()->input->get('field_id_'.$row['field_id'])) !== FALSE)
				{
					$field_data = ee()->functions->bm_qstr_decode(ee()->input->get('tb_url')."\n\n".$field_data );
				}
			}
			else
			{
				$field_data = (isset($entry_data['field_id_'.$row['field_id']])) ? $entry_data['field_id_'.$row['field_id']] : $field_data;
				$field_dt	= (isset($entry_data['field_dt_'.$row['field_id']])) ? $entry_data['field_dt_'.$row['field_id']] : 'y';
				$field_fmt	= (isset($entry_data['field_ft_'.$row['field_id']])) ? $entry_data['field_ft_'.$row['field_id']] : $field_fmt;
			}

			$settings = array(
				'field_instructions'	=> trim($row['field_instructions']),
				'field_text_direction'	=> ($row['field_text_direction'] == 'rtl') ? 'rtl' : 'ltr',
				'field_fmt'				=> $field_fmt,
				'field_dt'				=> $field_dt,
				'field_data'			=> $field_data,
				'field_name'			=> 'field_id_'.$row['field_id'],
			);

			$ft_settings = array();

			if (isset($row['field_settings']) && strlen($row['field_settings']))
			{
				$ft_settings = unserialize(base64_decode($row['field_settings']));
			}

			$settings = array_merge($row, $settings, $ft_settings);
			ee()->api_channel_fields->set_settings($row['field_id'], $settings);

			$field_settings[$settings['field_name']] = $settings;
		}

		// Merge the default and custom fields

		return array_merge($deft_fields, $field_settings);
	}

	// --------------------------------------------------------------------

	/**
	 * update/add field
	 *
	 * omit field_id in $field_data to create a new field
	 *
	 * @param array $field_data the field settings;
	 *                          uses the following keys: group_id, site_id, field_name, field_label, field_type, field_order,
	 *                          and also fieldtype-specific settings, e.g. text_field_text_direction.
	 *                          works in concert with data submitted using Api_channel_fields::field_edit_vars()
	 *
	 * @return int|string|FALSE the field_id or FALSE if the process failed
	 */
	public function update_field(array $field_data)
	{
		$this->errors = array();

		ee()->load->helper('array');

		if ( ! isset($field_data['group_id']))
		{
			$this->_set_error('unauthorized_access');

			return FALSE;
		}

		ee()->lang->loadfile('admin_content');

		// If the $field_id variable has data we are editing an
		// existing group, otherwise we are creating a new one

		$edit = ( ! isset($field_data['field_id']) OR $field_data['field_id'] == '') ? FALSE : TRUE;

		// We need this as a variable as we'll unset the array index

		$group_id = element('group_id', $field_data);

		// Check for required fields

		$error = array();
		ee()->load->model('field_model');

		// little check in case they switched sites in MSM after leaving a window open.
		// otherwise the landing page will be extremely confusing
		if ( ! isset($field_data['site_id']) OR $field_data['site_id'] != ee()->config->item('site_id'))
		{
			$this->_set_error('site_id_mismatch');
		}

		// Was a field name supplied?
		if ($field_data['field_name'] == '')
		{
			$this->_set_error('no_field_name');
		}
		// Is the field one of the reserved words?
		else if (in_array($field_data['field_name'], ee()->cp->invalid_custom_field_names()))
		{
			$this->_set_error('reserved_word');
		}

		// Was a field label supplied?
		if ($field_data['field_label'] == '')
		{
			$this->_set_error('no_field_label');
		}

		// Does field name contain invalid characters?
		if (preg_match('/[^a-z0-9\_\-]/i', $field_data['field_name']))
		{
			$this->errors[] = lang('invalid_characters').': '.$field_data['field_name'];
		}

		// Truncated field name to test against duplicates
		$trunc_field_name = substr(element('field_name', $field_data), 0, 32);

		// Is the field name taken?
		ee()->db->where(array(
			'site_id' => ee()->config->item('site_id'),
			'field_name' => $trunc_field_name,
		));

		if ($edit == TRUE)
		{
			ee()->db->where('field_id !=', element('field_id', $field_data));
		}

		if (ee()->db->count_all_results('channel_fields') > 0)
		{
			if ($trunc_field_name != element('field_name', $field_data))
			{
				$this->_set_error('duplicate_truncated_field_name');
			}
			else
			{
				$this->_set_error('duplicate_field_name');
			}
		}

		$field_type = $field_data['field_type'];

		// If they are setting a file type, ensure there is at least one upload directory available
		if ($field_type == 'file')
		{
			ee()->load->model('file_upload_preferences_model');
			$upload_dir_prefs = ee()->file_upload_preferences_model->get_file_upload_preferences();

			// count upload dirs
			if (count($upload_dir_prefs) === 0)
			{
				ee()->lang->loadfile('filemanager');
				$this->_set_error('please_add_upload');
			}
		}

		// Are there errors to display?

		if ($this->error_count() > 0)
		{
			return FALSE;
		}

		// Get the field type settings
		$this->fetch_all_fieldtypes();
		$this->setup_handler($field_type);
		$ft_settings = $this->apply('save_settings', array($this->get_posted_field_settings($field_type)));

		// Default display options
		foreach(array('smileys', 'glossary', 'spellcheck', 'formatting_btns', 'file_selector', 'writemode') as $key)
		{
			$tmp = $this->_get_ft_data($field_type, 'field_show_'.$key, $field_data);
			$ft_settings['field_show_'.$key] = $tmp ? $tmp : 'n';
		}

		// Now that they've had a chance to mess with the POST array,
		// grab post values for the native fields (and check namespaced fields)
		foreach($this->native as $key)
		{
			$native_settings[$key] = $this->_get_ft_data($field_type, $key, $field_data);
		}

		// Set some defaults
		$native_settings['field_list_items']		= ($tmp = $this->_get_ft_data($field_type, 'field_list_items', $field_data)) ? $tmp : '';

		$native_settings['field_text_direction']	= ($native_settings['field_text_direction'] !== FALSE) ? $native_settings['field_text_direction'] : 'ltr';
		$native_settings['field_show_fmt']			= ($native_settings['field_show_fmt'] !== FALSE) ? $native_settings['field_show_fmt'] : 'n';
		$native_settings['field_fmt']				= ($native_settings['field_fmt'] !== FALSE) ? $native_settings['field_fmt'] : 'xhtml';

		if ($native_settings['field_list_items'] != '')
		{
			// This results in double encoding later on
			//$native_settings['field_list_items'] = quotes_to_entities($native_settings['field_list_items']);
		}

		if ($native_settings['field_pre_populate'] == 'y')
		{
			$x = explode('_', $this->_get_ft_data($field_type, 'field_pre_populate_id', $field_data));

			$native_settings['field_pre_channel_id']	= $x['0'];
			$native_settings['field_pre_field_id'] = $x['1'];
		}

		// If they returned a native field value as part of their settings instead of changing the post array,
		// we'll merge those changes into our native settings

		foreach($ft_settings as $key => $val)
		{
			if (in_array($key, $this->native))
			{
				unset($ft_settings[$key]);
				$native_settings[$key] = $val;
			}
		}

		if ($field_data['field_order'] == 0 OR $field_data['field_order'] == '')
		{
			$query = ee()->db->select('MAX(field_order) as max')
					      ->where('site_id', ee()->config->item('site_id'))
					      ->where('group_id', (int) $group_id)
					      ->get('channel_fields');

			$native_settings['field_order'] = (int) $query->row('max') + 1;
		}

		$native_settings['field_settings'] = base64_encode(serialize($ft_settings));

		// Construct the query based on whether we are updating or inserting
		if ($edit === TRUE)
		{
			if ( ! is_numeric($native_settings['field_id']))
			{
				return FALSE;
			}

			// Update the formatting for all existing entries
			if ($this->_get_ft_data($field_type, 'update_formatting', $field_data) == 'y')
			{
				ee()->db->update(
					'channel_data',
					array('field_ft_'.$native_settings['field_id'] => $native_settings['field_fmt'])
				);
			}


			// Send it over to drop old fields, add new ones, and modify as needed
			$this->edit_datatype(
				$native_settings['field_id'],
				$field_type,
				$native_settings
			);

			unset($native_settings['group_id']);

			ee()->db->where('field_id', $native_settings['field_id']);
			ee()->db->where('group_id', $group_id);
			ee()->db->update('channel_fields', $native_settings);

			// Update saved layouts if necessary
			$collapse = ($native_settings['field_is_hidden'] == 'y') ? TRUE : FALSE;
			$buttons = ($ft_settings['field_show_formatting_btns'] == 'y') ? TRUE : FALSE;

			// Add to any custom layouts
			// First, figure out what channels are associated with this group
			// Then using the list of channels, figure out the layouts associated with those channels
			// Then update each layout individually

			$channels_for_group = ee()->field_model->get_assigned_channels($group_id);

			if ($channels_for_group->num_rows() > 0)
			{
				ee()->load->model('layout_model');

				foreach ($channels_for_group->result() as $channel)
				{
					$channel_ids[] = $channel->channel_id;
				}

				ee()->db->select('layout_id');
				ee()->db->where_in('channel_id', $channel_ids);
				$layouts_for_group = ee()->db->get('layout_publish');

				foreach ($layouts_for_group->result() as $layout)
				{
					// Figure out visibility for the field in the layout
					$layout_settings = ee()->layout_model->get_layout_settings(array('layout_id' => $layout->layout_id), TRUE);

					$visibility = TRUE;
					$width = '100%';

					if (array_key_exists('field_id_'.$native_settings['field_id'], $layout_settings))
					{
						$field_settings = $layout_settings['field_id_'.$native_settings['field_id']];

						$width = ($field_settings['width'] !== NULL) ?
							$field_settings['width'] :
							$width;

						$visibility = ($field_settings['visible'] !== NULL) ?
							$field_settings['visible'] :
							$visibility;
					}

					$field_info[$native_settings['field_id']] = array(
						'visible'     => $visibility,
						'collapse'    => $collapse,
						'htmlbuttons' => $buttons,
						'width'       => $width
					);

					ee()->layout_model->edit_layout_group_fields($field_info, $layout->layout_id);
				}
			}
		}
		else
		{
			if ( ! $native_settings['field_ta_rows'])
			{
				$native_settings['field_ta_rows'] = 0;
			}

			// as its new, there will be no field id, unset it to prevent an empty string from attempting to pass
			unset($native_settings['field_id']);

			ee()->db->insert('channel_fields', $native_settings);

			$insert_id = ee()->db->insert_id();
			$native_settings['field_id'] = $insert_id;

			$this->add_datatype(
				$insert_id,
				$native_settings
			);

			ee()->db->update('channel_data', array('field_ft_'.$insert_id => $native_settings['field_fmt']));

			ee()->db->update('channel_data', array('field_ft_'.$insert_id => $native_settings['field_fmt']));

			$field_formatting = array('none', 'br', 'markdown', 'xhtml');

			//if the selected field formatting is not one of the native formats, make sure it gets added to exp_field_formatting for this field
			if ( ! in_array($native_settings['field_fmt'], $field_formatting))
			{
				$field_formatting[] = $native_settings['field_fmt'];
			}

			foreach ($field_formatting as $val)
			{
				$f_data = array('field_id' => $insert_id, 'field_fmt' => $val);
				ee()->db->insert('field_formatting', $f_data);
			}

			$collapse = ($native_settings['field_is_hidden'] == 'y') ? TRUE : FALSE;
			$buttons = ($ft_settings['field_show_formatting_btns'] == 'y') ? TRUE : FALSE;

			$field_info['publish'][$insert_id] = array(
								'visible'		=> 'true',
								'collapse'		=> $collapse,
								'htmlbuttons'	=> $buttons,
								'width'			=> '100%'
			);

			// Add to any custom layouts
			$query = ee()->field_model->get_assigned_channels($group_id);

			if ($query->num_rows() > 0)
			{
				foreach ($query->result() as $row)
				{
					$channel_ids[] = $row->channel_id;
				}

				ee()->load->library('layout');
				ee()->layout->add_layout_fields($field_info, $channel_ids);
			}
		}

		$_final_settings = array_merge($native_settings, $ft_settings);
		unset($_final_settings['field_settings']);

		$this->set_settings($native_settings['field_id'], $_final_settings);
		$this->setup_handler($native_settings['field_id']);
		$this->apply('post_save_settings', array($_final_settings));

		ee()->functions->clear_caching('all', '');

		return $native_settings['field_id'];
	}

	// --------------------------------------------------------------------

	/**
	 * Creates an array of field settings to pass to a fieldtype's validate_settings
	 * and save_settings methods
	 *
	 * @return mixed the fieldtype setting requested
	 */
	public function get_posted_field_settings($field_type)
	{
		$keys = array_merge(
			$this->native,
			preg_grep('/^'.$field_type.'_.*/', array_keys($_POST))
		);

		$posted = array();
		foreach($keys as $key)
		{
			if (isset($_POST[$key]))
			{
				$posted[$key] = $_POST[$key];
			}
		}

		return $posted;
	}

	// --------------------------------------------------------------------

	/**
	 * A utility to get fieldtype-specific settings from the $field_data array
	 * supplied by Api_channel_fields::update_field()
	 *
	 * @param string $field_type the name of the field_type, e.g. text or select
	 * @param string $key the key of the setting to retrieve, e.g. field_text_direction
	 * @param array $field_data the full array of settings provided by the update_field() method
	 *
	 * @return mixed the fieldtype setting requested
	 */
	protected function _get_ft_data($field_type, $key, $field_data)
	{
		if (isset($field_data[$key]))
		{
			return $field_data[$key];
		}

		$key = $field_type.'_'.$key;

		return (isset($field_data[$key])) ? $field_data[$key] : FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * gets variables to be passed to the admin/field_edit view
	 * for new fields or existing fields
	 *
	 * @param string|int $group_id
	 * @param string|int $field_id optional if new field
	 * @param array|false $field_types array of field types to present as field_type_options,
	 * 				   will show all valid field types if FALSE
	 *
	 * @return array    the default fields needed to use the admin/field_edit view
	 */
	public function field_edit_vars($group_id, $field_id = FALSE, $field_types = FALSE)
	{
		$this->errors = array();

		ee()->load->library('table');

		ee()->load->model('field_model');

		$vars = array(
			'group_id' => $group_id,
			'field_id' => $field_id,
		);

		ee()->db->select('f.*');
		ee()->db->from('channel_fields AS f, field_groups AS g');
		ee()->db->where('f.group_id = g.group_id');
		ee()->db->where('g.site_id', ee()->config->item('site_id'));
		ee()->db->where('f.field_id', $vars['field_id']);

		$field_query = ee()->db->get();

		if ($field_id == '')
		{
			$type = 'new';

			foreach ($field_query->list_fields() as $f)
			{
				if ( ! isset($vars[$f]))
				{
					$vars[$f] = '';
				}
			}

			ee()->db->select('group_id');
			ee()->db->where('group_id', $vars['group_id']);
			ee()->db->where('site_id', ee()->config->item('site_id'));
			$query = ee()->db->get('channel_fields');

			$vars['field_order'] = $query->num_rows() + 1;

			if ($query->num_rows() > 0)
			{
				$vars['group_id'] = $query->row('group_id');
			}
			else
			{
				// if there are no existing fields yet for this group, this allows us to still validate the group_id
				ee()->db->where('group_id', $vars['group_id']);
				ee()->db->where('site_id', ee()->config->item('site_id'));

				if (ee()->db->count_all_results('field_groups') != 1)
				{
					$this->_set_error('unauthorized_access');

					return FALSE;
				}
			}
		}
		else
		{
			$type = 'edit';

			// No valid edit id?  No access
			if ($field_query->num_rows() == 0)
			{
				$this->_set_error('unauthorized_access');

				return FALSE;
			}

			foreach ($field_query->row_array() as $key => $val)
			{
				if ($key == 'field_settings' && $val)
				{
					$ft_settings = unserialize(base64_decode($val));
					$vars = array_merge($vars, $ft_settings);
				}
				else
				{
					$vars[$key] = $val;
				}
			}

			$vars['update_formatting']	= FALSE;
		}

		extract($vars);

		// Fetch the name of the group
		$query = ee()->field_model->get_field_group($group_id);

		$vars['group_name']			= $query->row('group_name');
		$vars['submit_lang_key']	= ($type == 'new') ? 'submit' : 'update';

		// Fetch the channel names

		ee()->db->select('channel_id, channel_title, field_group');
		ee()->db->where('site_id', ee()->config->item('site_id'));
		ee()->db->order_by('channel_title', 'asc');
		$query = ee()->db->get('channels');

		$vars['field_pre_populate_id_options'] = array();

		foreach ($query->result_array() as $row)
		{
			// Fetch the field names
			ee()->db->select('field_id, field_label');
			ee()->db->where('group_id', $row['field_group']);
			ee()->db->order_by('field_label','ASC');
			$rez = ee()->db->get('channel_fields');

			if ($rez->num_rows() > 0)
			{
				$vars['field_pre_populate_id_options'][$row['channel_title']] = array();

				foreach ($rez->result_array() as $frow)
				{
					$vars['field_pre_populate_id_options'][$row['channel_title']][$row['channel_id'].'_'.$frow['field_id']] = $frow['field_label'];
				}
			}
		}

		$vars['field_pre_populate_id_select'] = $field_pre_channel_id.'_'.$field_pre_field_id;

		// build list of formatting options
		if ($type == 'new')
		{
			$vars['edit_format_link'] = '';

			ee()->load->model('addons_model');

			$vars['field_fmt_options'] = ee()->addons_model->get_plugin_formatting(TRUE);
		}
		else
		{
			$confirm = "onclick=\"if( !confirm('".lang('list_edit_warning')."')) return false;\"";
			$vars['edit_format_link'] = '<strong><a '.$confirm.' href="'.BASE.AMP.'C=admin_content'.AMP.'M=edit_formatting_options'.AMP.'id='.$field_id.'" title="'.lang('edit_list').'">'.lang('edit_list').'</a></strong>';

			ee()->db->select('field_fmt');
			ee()->db->where('field_id', $field_id);
			ee()->db->order_by('field_fmt');
			$query = ee()->db->get('field_formatting');

			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					$name = ucwords(str_replace('_', ' ', $row['field_fmt']));

					if ($name == 'Br')
					{
						$name = lang('auto_br');
					}
					elseif ($name == 'Xhtml')
					{
						$name = lang('xhtml');
					}
					$vars['field_fmt_options'][$row['field_fmt']] = $name;
				}
			}
		}

		$vars['field_fmt'] = (isset($field_fmt) && $field_fmt != '') ? $field_fmt : 'none';

		// Prep our own fields

		$fts = $this->fetch_installed_fieldtypes();

		$default_values = array(
			'field_type'					=> isset($fts['text']) ? 'text' : key($fts),
			'field_show_fmt'				=> set_value('field_show_fmt', 'n'),
			'field_required'				=> set_value('field_required', 'n'),
			'field_search'					=> set_value('field_search', 'n'),
			'field_is_hidden'				=> set_value('field_is_hidden', 'n'),
			'field_pre_populate'			=> set_value('field_pre_populate', 'n'),
			'field_show_spellcheck'			=> set_value('field_show_spellcheck', 'n'),
			'field_show_smileys'			=> set_value('field_show_smileys', 'n'),
			'field_show_glossary'			=> set_value('field_show_glossary', 'n'),
			'field_show_formatting_btns'	=> set_value('field_show_formatting_btns', 'n'),
			'field_show_writemode'			=> set_value('field_show_writemode', 'n'),
			'field_show_file_selector'		=> set_value('field_show_file_selector', 'n'),
			'field_text_direction'			=> set_value('field_text_direction', 'ltr')
		);

		foreach($default_values as $key => $val)
		{
			$vars[$key] = ( ! isset($vars[$key]) OR $vars[$key] == '') ? $val : $vars[$key];
		}

		foreach(array('field_pre_populate', 'field_required', 'field_search', 'field_show_fmt') as $key)
		{
			$current = ($vars[$key] == 'y') ? 'y' : 'n';
			$other = ($current == 'y') ? 'n' : 'y';

			$vars[$key.'_'.$current] = TRUE;
			$vars[$key.'_'.$other] = FALSE;
		}

		// Text Direction
		$current = $vars['field_text_direction'];
		$other = ($current == 'rtl') ? 'ltr' : 'rtl';

		$vars['field_text_direction_'.$current] = TRUE;
		$vars['field_text_direction_'.$other] = FALSE;

		// Grab Field Type Settings

		$vars['field_type_table']	= array();
		$vars['field_type_options']	= array();

		$created = FALSE;

		foreach($fts as $key => $attr)
		{
			if (is_array($field_types) && ! in_array($key, $field_types))
			{
				continue;
			}

			// Global settings
			$settings = unserialize(base64_decode($fts[$key]['settings']));

			$settings['field_type'] = $key;

			ee()->table->clear();

			$this->set_settings($key, $settings);
			$this->setup_handler($key);
			$this->apply('_init', array(
				array('id' => $field_id)
			));

			$str = $this->apply('display_settings', array($vars));

			$vars['field_type_tables'][$key]	= $str;
			$vars['field_type_options'][$key]	= $attr['name'];

			if (count(ee()->table->rows))
			{
				$vars['field_type_tables'][$key] = ee()->table->rows;
			}
		}

		asort($vars['field_type_options']);	// sort by title

		$vars['form_hidden'] = array(
			'group_id'		=> $group_id,
			'field_id'		=> $field_id,
			'site_id'		=> ee()->config->item('site_id')
		);

		$vars['ft_selector'] = "#ft_".implode(", #ft_", array_keys($fts));

		return $vars;
	}

	// --------------------------------------------------------------------

	/**
	 * Gets field pair template tags for a specified field name in specified
	 * tag data with an optional prefix
	 *
	 * @param	string	Tag data
	 * @param	string	Field name to get variables for
	 * @param	string	Optional tag prefix, i.e. for Relationships or Grid
	 * @return	array	Structured tag pair template tags
	 */
	public function get_pair_field($tagdata, $field_name, $prefix = '')
	{
		$pfield_chunk = array();
		$offset = 0;
		$field_name = $prefix.$field_name;

		while (($end = strpos($tagdata, LD.'/'.$field_name, $offset)) !== FALSE)
		{
			// This hurts soo much. Using custom fields as pair and single vars in the same
			// channel tags could lead to something like this: {field}...{field}inner{/field}
			// There's no efficient regex to match this case, so we'll find the last nested
			// opening tag and re-cut the chunk.

			if (preg_match("/".LD."{$field_name}((?::\S+)?)(\s.*?)?".RD."(.*?)".LD.'\/'."{$field_name}\\1".RD."/s", $tagdata, $matches, 0, $offset))
			{
				$chunk = $matches[0];
				$modifier = $matches[1];
				$params = $matches[2];
				$content = $matches[3];

				// We might've sandwiched a single tag - no good, check again (:sigh:)
				if ((strpos($chunk, LD.$field_name.$modifier, 1) !== FALSE) && preg_match_all("/".LD."{$field_name}{$modifier}(\s.*?)?".RD."/s", $chunk, $match))
				{
					// Let's start at the end
					$idx = count($match[0]) - 1;
					$tag = $match[0][$idx];

					// Reassign the parameter
					$params = $match[1][$idx];

					// Cut the chunk at the last opening tag
					$offset = strrpos($chunk, $tag);
					$chunk = substr($chunk, $offset);
					$chunk = strstr($chunk, LD.$field_name);
					$content = substr($chunk, strlen($tag), -strlen(LD.'/'.$field_name.RD));
				}

				$params = ee()->functions->assign_parameters($params);
				$params = $params ? $params : array();

				$chunk_array = array(
					ltrim($modifier, ':'),
					$content,
					$params,
					$chunk
				);

				$pfield_chunk[] = $chunk_array;
			}

			$offset = $end + 1;
		}

		return $pfield_chunk;
	}

	// --------------------------------------------------------------------

	/**
	 * Gets information for a single variable field in a template
	 *
	 * @param	string	Tag to get field name, modifier and params from
	 * @param	string	Optional prefix
	 * @return	array	Field name, modifier and params for field
	 */
	public function get_single_field($tag, $prefix = '')
	{
		$field_info = array();

		$unprefixed_tag	= preg_replace('/^'.$prefix.'/', '', $tag);
		$field_name 	= substr($unprefixed_tag.' ', 0, strpos($unprefixed_tag.' ', ' '));
		$param_string	= substr($unprefixed_tag.' ', strlen($field_name));

		$modifier = '';
		$modifier_loc = strpos($field_name, ':');

		if ($modifier_loc !== FALSE)
		{
			$modifier = substr($field_name, $modifier_loc + 1);
			$field_name = substr($field_name, 0, $modifier_loc);
		}

		$field_info['field_name'] = $field_name;
		$field_info['params'] = ($param_string) ? ee()->functions->assign_parameters($param_string) : array();
		$field_info['modifier'] = $modifier;

		return $field_info;
	}

	// --------------------------------------------------------------------

	/**
	 * Notify any extensions of incoming field type data
	 *
	 * @param	object	Fieldtype that will be called
	 * @param	string	Method that will be called on the fieldtype
	 * @param	mixed	Fieldtype parameters
	 * @return	mixed	Modified fieldtype data
	 */
	public function custom_field_data_hook(EE_Fieldtype $obj, $method, $parameters)
	{
		// -------------------------------------------
		// 'custom_field_modify_data' hook.
		//  - Modify the parameters passed to the fieldtype
		//  - Can be used to modify the fieldtype prior to most fieldtype functions
		//  - Please be careful with that second option.
		//
			if (ee()->extensions->active_hook('custom_field_modify_data') === TRUE)
			{
				return ee()->extensions->universal_call('custom_field_modify_data', $obj, $method, $parameters);
			}
		//
		// -------------------------------------------

		return $parameters;
	}
}

// END Api_channel_fields class

/* End of file Api_channel_fields.php */
/* Location: ./system/expressionengine/libraries/api/Api_channel_fields.php */
