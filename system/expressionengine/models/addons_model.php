<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Admin Model
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Addons_model extends CI_Model {


	/**
	 * Get Plugin Formatting
	 *
	 * Used in various locations to list formatting options
	 *
	 * @access	public
	 * @param	bool	whether or not to include a "None" option
	 * @return	array
	 */
	function get_plugin_formatting($include_none = FALSE)
	{
		$this->load->helper('directory');
		
		static $filelist = array();
		
		$exclude	= array('auto_xhtml');
		$default	= array('br' => $this->lang->line('auto_br'), 'xhtml' => 'Xhtml');
		
		if ($include_none === TRUE)
		{
			$default['none'] = $this->lang->line('none');
		}
		
		if ( ! count($filelist))
		{
			$ext_len = strlen('.php');

			// first party plugins
			if (($map = directory_map(PATH_PI, TRUE)) !== FALSE)
			{
				foreach ($map as $file)
				{
					if (strncasecmp($file, 'pi.', 3) == 0 && 
						substr($file, -$ext_len) == '.php' && 
						strlen($file) > strlen('pi..php'))
					{
						$file = substr($file, 3, -strlen('.php'));						
						$filelist[$file] = ucwords(str_replace('_', ' ', $file));
					}				
				}
			}


			// now third party add-ons, which are arranged in "packages"
			// only catch files that match the package name, as other files are merely assets
			if (($map = directory_map(PATH_THIRD, 2)) !== FALSE)
			{
				foreach ($map as $pkg_name => $files)
				{
					if ( ! is_array($files))
					{
						$files = array($files);
					}

					foreach ($files as $file)
					{
						if (is_array($file))
						{
							// we're only interested in the top level files for the addon
							continue;
						}

						// how abouts a plugin?
						elseif (strncasecmp($file, 'pi.', 3) == 0 && 
								substr($file, -$ext_len) == '.php' && 
								strlen($file) > strlen('pi..php'))
						{							
							$file = substr($file, 3, -$ext_len);

							if ($file == $pkg_name)
							{
								$filelist[$pkg_name] = ucwords(str_replace('_', ' ', $pkg_name));
							}
						}					
					}
				}
			}
		}
		
		$return = $default + $filelist;

		ksort($return);
		return $return;
	}

	// --------------------------------------------------------------------

	/**
	 * Get Plugins
	 *
	 * @access	public
	 * @return	array
	 */
	function get_plugins()
	{		
		$this->load->helper('directory');

		$plugins = array();
		$info 	= array();
		$ext_len = strlen('.php');

		// first party plugins
		if (($map = directory_map(PATH_PI, TRUE)) !== FALSE)
		{
			foreach ($map as $file)
			{
				if (strncasecmp($file, 'pi.', 3) == 0 && substr($file, -$ext_len) == '.php' && strlen($file) > strlen('pi..php'))
				{
					if ( ! @include_once(PATH_PI.$file))
					{
						continue;
					}

					$name = substr($file, 3, -$ext_len);

					$plugins[] = $name;

					$info[$name] = array_unique($plugin_info);
				}				
			}
		}

		// now third party add-ons, which are arranged in "packages"
		// only catch files that match the package name, as other files are merely assets
		if (($map = directory_map(PATH_THIRD, 2)) !== FALSE)
		{
			foreach ($map as $pkg_name => $files)
			{
				if ( ! is_array($files))
				{
					$files = array($files);
				}
				
				foreach ($files as $file)
				{
					if (is_array($file))
					{
						// we're only interested in the top level files for the addon
						continue;
					}
					
					elseif (strncasecmp($file, 'pi.', 3) == 0 && 
							substr($file, -$ext_len) == '.php' && 
							strlen($file) > strlen('pi..php'))
					{
						if ( ! class_exists(ucfirst($pkg_name)))
						{
							if ( ! @include_once(PATH_THIRD.$pkg_name.'/'.$file))
							{
								continue;
							}
						}

						$plugins[] = $pkg_name;

						$info[$pkg_name] = array_unique($plugin_info);
					}					
				}
			}
		}

		return $info;
	}

	// --------------------------------------------------------------------

	/**
	 * Get Installed Modules
	 *
	 * @access	public
	 * @return	array
	 */
	function get_installed_modules($has_cp = FALSE, $has_tab = FALSE)
	{
		$this->db->select('LOWER(module_name) AS module_name, module_version, has_cp_backend, module_id', FALSE);
		
		if ($has_cp === TRUE)
		{
			$this->db->where('has_cp_backend', 'y');
		}

		if ($has_tab === TRUE)
		{
			$this->db->where('has_publish_fields', 'y');
		}
		
		return $this->db->get('modules');
	}
	
	// --------------------------------------------------------------------

	/**
	 * Get Installed Extensions
	 *
	 * @access	public
	 * @return	array
	 */
	function get_installed_extensions($enabled = TRUE)
	{
		$this->db->select('class, version');
		
		if ($enabled)
		{
			$this->db->where('enabled', 'y');
		}
		else
		{
			$this->db->select('enabled');
		}

		return $this->db->get('extensions');
	}

	// --------------------------------------------------------------------

	/**
	 * Module installed
	 *
	 * Returns true if a module is installed, false if not
	 *
	 * @access	public
	 * @param	string
	 * @return	boolean
	 */
	function module_installed($module_name)
	{
		static $_installed = array();
		
		if ( ! isset($_installed[$module_name]))
		{
			$this->db->from("modules");
			$this->db->where("module_name", ucfirst(strtolower($module_name)));
			$_installed[$module_name] = ($this->db->count_all_results() > 0) ? TRUE : FALSE;
		}
		
		return $_installed[$module_name];
	}
	
	// --------------------------------------------------------------------

	/**
	 * Accessory installed
	 *
	 * Returns true if an accessory is installed, false if not
	 *
	 * @access	public
	 * @param	string
	 * @return	boolean
	 */
	function accessory_installed($acc_name)
	{
		static $_installed = array();
		
		if ( ! isset($_installed[$acc_name]))
		{
			$this->db->from("accessories");
			$this->db->where("class", ucfirst(strtolower($acc_name.'_acc')));
			$_installed[$acc_name] = ($this->db->count_all_results() > 0) ? TRUE : FALSE;
		}
		
		return $_installed[$acc_name];
	}
	
	// --------------------------------------------------------------------

	/**
	 * Extension installed
	 *
	 * Returns true if an extension is installed, false if not
	 *
	 * @access	public
	 * @param	string
	 * @return	boolean
	 */
	function extension_installed($ext_name)
	{
		static $_installed = array();
		
		if ( ! isset($_installed[$ext_name]))
		{
			$this->db->from("extensions");
			$this->db->where("class", ucfirst(strtolower($ext_name.'_ext')));
			$_installed[$ext_name] = ($this->db->count_all_results() > 0) ? TRUE : FALSE;
		}
		
		return $_installed[$ext_name];
	}
	
	// --------------------------------------------------------------------

	/**
	 * Fieldtype installed
	 *
	 * Returns true if a fieldtype is installed, false if not
	 *
	 * @access	public
	 * @param	string
	 * @return	boolean
	 */
	function fieldtype_installed($ft_name)
	{
		static $_installed = array();
		
		if ( ! isset($_installed[$ft_name]))
		{
			$this->db->from("fieldtypes");
			$this->db->where("name", strtolower($ft_name));
			$_installed[$ft_name] = ($this->db->count_all_results() > 0) ? TRUE : FALSE;
		}
		
		return $_installed[$ft_name];
	}

	// --------------------------------------------------------------------

	/**
	 * RTE Tool installed
	 *
	 * Returns true if a RTE tool is installed, false if not
	 *
	 * @access	public
	 * @param	string
	 * @return	boolean
	 */
	function rte_tool_installed($tool_name)
	{
		// Is the module even installed?
		if ( ! $this->db->table_exists('rte_tools'))
		{
			return FALSE;
		}

		static $_installed = array();
		
		if ( ! isset($_installed[$tool_name]))
		{
			$this->db->from("rte_tools");
			$this->db->where("name", ucfirst(strtolower(str_replace(' ', '_', $tool_name))));
			$_installed[$tool_name] = ($this->db->count_all_results() > 0) ? TRUE : FALSE;
		}
		
		return $_installed[$tool_name];
	}
	
	// --------------------------------------------------------------------

	/**
	 * Update an Extension
	 *
	 * @access	public
	 * @return	void
	 */
	function update_extension($class, $data)
	{
		$this->db->set($data);
		$this->db->where('class', $class);
		$this->db->update('extensions');
	}

	// --------------------------------------------------------------------

	/**
	 * Update Accessory Information
	 *
	 * @access	public
	 * @return	void
	 */
	function update_accessory($class, $data)
	{
		// allow either "class", or "class_acc" to be passed
		if (substr($class, -4) != '_acc')
		{
			$class = $class.'_acc';
		}

		$this->db->where('class', $class);
		$this->db->update('accessories', $data);
	}
}

/* End of file addons_model.php */
/* Location: ./system/expressionengine/models/addons_model.php */