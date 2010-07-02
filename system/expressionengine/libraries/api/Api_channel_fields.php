<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Api_channel_fields extends Api {

	var $field_types		= array();
	var $ft_paths			= array();
	var $settings			= array();

	var $ee_base_ft			= FALSE;
	var $global_settings;

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function Api_channel_fields()
	{
		parent::Api();
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
		$this->EE->load->library('addons');
		$fts = $this->EE->addons->$method('fieldtypes');
		
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
		$this->EE->db->select('field_id, field_type, field_name, site_id, field_settings');
		$query = $this->EE->db->get('channel_fields');
		
		$cfields = array();
		$dfields = array();
		$rfields = array();
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
			elseif ($row['field_type'] == 'rel')
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

				$this->set_settings($row['field_id'], $settings);
			}
			
			
			$cfields[$row['site_id']][$row['field_name']] = $row['field_id'];
		}

		return array(
			'custom_channel_fields'	=> $cfields,
			'date_fields'			=> $dfields,
			'relationship_fields'	=> $rfields,
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
			$file = 'ft.'.$field_type.EXT;
			$path = PATH_FT;
			
			if ( ! file_exists($path.$file))
			{
				$path = PATH_THIRD.$field_type.'/';
				
				if ( ! file_exists($path.$file))
				{
					show_error(sprintf($this->EE->lang->line('unable_to_load_field_type'),
					 						strtolower($file)));
				}
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
		
		$this->EE->load->add_package_path($_ft_path);
		$obj =& instantiate_class(new $class());
		$this->EE->load->remove_package_path($_ft_path);
		
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
		$_old_view_path = $this->EE->load->_ci_view_path;
		$_ft_path = $this->ft_paths[$this->field_type];
		
		$this->EE->load->_ci_view_path = $_ft_path.'views/';
		$this->EE->load->add_package_path($_ft_path);
		
		$res = call_user_func_array(array(&$this->field_types[$this->field_type], $method), $parameters);
		
		$this->EE->load->remove_package_path($_ft_path);
		$this->EE->load->_ci_view_path = $_old_view_path;
		
		return $res;
	}

	// --------------------------------------------------------------------

	function set_datatype($field_id, $c_type)
	{		
		$this->EE->load->dbforge();
		
		$fields = array(
				'field_id_'.$field_id => array(
						'name'	=> 'field_id_'.$field_id
					)
			);
	
		switch($c_type)
		{
			case 'numeric':
				$fields['field_id_'.$field_id]['type'] = 'FLOAT';
				$fields['field_id_'.$field_id]['default'] = 0;
				break;
			case 'integer':
				$fields['field_id_'.$field_id]['type'] = 'INT';
				$fields['field_id_'.$field_id]['default'] = 0;
				break;
			default:
				$fields['field_id_'.$field_id]['type'] = 'text';
				$fields['field_id_'.$field_id]['null'] = TRUE;
		}
	
		$this->EE->dbforge->modify_column('channel_data', $fields);		
	}

	
	// --------------------------------------------------------------------
	
	/**
	 * Get custom field info from modules
	 *
	 * @access	public
	 */	
	function get_required_fields($channel_id)
	{
		$this->EE->load->model('channel_model');
		$required = array('title', 'entry_date', 'url_title');
		
		$query = $this->EE->channel_model->get_channel_info($channel_id, array('field_group'));
		
		if ($query->num_rows() > 0)
		{
			$row = $query->row();
			$fields = $this->EE->channel_model->get_required_fields($row->field_group);
		
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

			$this->EE->load->add_package_path($mod_base_path);

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
		$this->EE->load->remove_package_path($mod_base_path);
		
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

			$this->EE->load->add_package_path($mod_base_path);

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

					// add the view paths
					$orig_view_path = $this->EE->load->_ci_view_path;
					$this->EE->load->_ci_view_path = $mod_base_path.'views/';

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
					
					// restore our package and view paths
					$this->EE->load->_ci_view_path = $orig_view_path;

				}
			}
		
		// restore our package and view paths
		$this->EE->load->remove_package_path($mod_base_path);
		
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
				if (file_exists($tmp_path.$name.'/tab.'.$name.EXT))
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
					show_error(sprintf($this->EE->lang->line('unable_to_load_tab'), 'tab.'.$name.EXT));
				}
				
				include_once($paths[$name].'tab.'.$name.EXT);
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
		$this->EE->load->model('addons_model');
		$custom_field_modules = FALSE;
		
		$mquery = $this->EE->addons_model->get_installed_modules(FALSE, TRUE);
			
		if ($mquery->num_rows() > 0)
		{
			foreach($mquery->result_array() as $row)
			{
				$custom_field_modules[] = $row['module_name'];
			}
		}
		
		return $custom_field_modules;
	}
}

// END Api_channel_fields class

/* End of file Api_channel_fields.php */
/* Location: ./system/expressionengine/libraries/api/Api_channel_fields.php */