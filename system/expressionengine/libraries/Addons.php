<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Core Addons Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class EE_Addons {

	var $EE;
	var $_map;						// addons sorted by addon_type (plural)
	var $_packages = array();		// contains references to _map by package name

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function __construct()
	{
		$this->EE =& get_instance();
	}
	
	// --------------------------------------------------------------------

	/**
	 * Addon File Handler
	 *
	 * @access	private
	 * @param	string
	 * @return	void
	 */
	function get_files($type = 'modules')
	{
		$type_ident = array(
			'modules'		=> 'mcp',
			'extensions'	=> 'ext',
			'accessories'	=> 'acc',
			'plugins'		=> 'pi',
			'fieldtypes'	=> 'ft'
		);
		
		if ( ! is_array($this->_map))
		{
			$this->EE->load->helper('directory');
			
			// Initialize the _map array so if no addons of a certain type
			// are found, we can still return _map[$type] without errors
			
			$this->_map = array(
				'modules'		=> array(),
				'extensions'	=> array(),
				'accessories'	=> array(),
				'plugins'		=> array(),
				'fieldtypes'	=> array()
			);
			
			// First party is plural, third party is singular
			// so we need some inflection references
			
			$_plural_map = array(
					'modules'		=> 'module',
					'extensions'	=> 'extension',
					'plugins'		=> 'plugin',
					'accessories'	=> 'accessory',
					'fieldtypes'	=> 'fieldtype'
			);


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

    					foreach($type_ident as $addon_type => $ident)
    					{
    						if ($file == $ident.'.'.$pkg_name.EXT)
    						{
    							// Plugin classes don't have a suffix
    							$class = ($ident == 'pi') ? ucfirst($pkg_name) : ucfirst($pkg_name).'_'.$ident;

    							$this->_map[$addon_type][$pkg_name] = array(
																	'path'	=> PATH_THIRD.$pkg_name.'/',
																	'file'	=> $file,
																	'name'	=> ucwords(str_replace('_', ' ', $pkg_name)),
																	'class'	=> $class,
																	'package' => $pkg_name
    																	);

    							// Add cross-reference for package lookups - singular keys
    							$this->_packages[$pkg_name][$_plural_map[$addon_type]] =& $this->_map[$addon_type][$pkg_name];

    							break;
    						}
    					}
    				}
    			}			    
			}
			
			ksort($this->_map[$type]);
			ksort($this->_packages);
		}
		
		// And now first party addons - will override any third party packages of the same name.
		// We can be a little more efficient here and only check the directory they asked for
		
		static $_fp_read = array();
		
		// is_package calls this function with a blank key to skip
		// first party - we'll do that right here instead of checking
		// if the folder exists
		if ( ! array_key_exists($type, $type_ident))
		{
			return array();
		}
		
		if ( ! in_array($type, $_fp_read))
		{
			$this->EE->load->helper('file');

			$ext_len = strlen(EXT);
			
			$abbr = $type_ident[$type];

			$root_path = ($abbr == 'mcp') ? PATH_MOD : constant('PATH_'.strtoupper($abbr));
			
			$list = get_filenames($root_path);

			if (is_array($list))
			{
				foreach ($list as $file)
				{
					if (strncasecmp($file, $abbr.'.', strlen($abbr.'.')) == 0 && substr($file, -$ext_len) == EXT && strlen($file) > strlen($abbr.'.'.EXT))
					{
						$name	= substr($file, strlen($abbr.'.'), - $ext_len);
						$class	= ($abbr == 'pi') ? ucfirst($name) : ucfirst($name).'_'.$abbr;
						$path = ($abbr == 'ext' OR $abbr == 'acc' OR $abbr == 'ft') ? constant('PATH_'.strtoupper($abbr)) : $root_path.$name.'/';
						
						$this->_map[$type][$name] = array(
															'path'	=> $path,
															'file'	=> $file,
															'name'	=> ucwords(str_replace('_', ' ', $name)),
															'class'	=> $class
														);
					}
				}
			}

			$_fp_read[] = $type;

			ksort($this->_map[$type]);
		}

		return $this->_map[$type];
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Get information on what's installed
	 *
	 * @access	private
	 * @param	string
	 * @return	void
	 */
	function get_installed($type = 'modules')
	{
		static $_installed = array();
		
		if (isset($_installed[$type]))
		{
			return $_installed[$type];
		}
		
		$_installed[$type] = array();
		
		$this->EE->load->model('addons_model');
		
		if ($type == 'modules')
		{
			$query = $this->EE->addons_model->get_installed_modules();
			
			if ($query->num_rows() > 0)
			{
				$files = $this->get_files('modules');
				
				foreach($query->result_array() as $row)
				{
					if (isset($files[$row['module_name']]))
					{
						$_installed[$type][$row['module_name']] = array_merge($files[$row['module_name']], $row);
					}
				}
			}
		}
		elseif ($type == 'accessories')
		{
			$query = $this->EE->db->get('accessories');

			if ($query->num_rows() > 0)
			{
				$files = $this->get_files('accessories');

				foreach ($query->result_array() as $row)
				{
					$name = strtolower(substr($row['class'], 0, -4));
					
					if (isset($files[$name]))
					{
						$_installed[$type][$name] = array_merge($files[$name], $row);
					}
				}
			}
		}
		elseif ($type == 'extensions')
		{
			$query = $this->EE->addons_model->get_installed_extensions();
			
			if ($query->num_rows() > 0)
			{
				$files = $this->get_files('extensions');
				
				foreach($query->result_array() as $row)
				{
					$name = strtolower(substr($row['class'], 0, -4));

					if (isset($files[$name]))
					{
						$_installed[$type][$name] = array_merge($files[$name], $row);
					}
				}
			}
		}
		elseif ($type == 'fieldtypes')
		{
			$query = $this->EE->db->get('fieldtypes');
			
			if ($query->num_rows() > 0)
			{
				$files = $this->get_files('fieldtypes');
				
				foreach($query->result_array() as $row)
				{
					$name = $row['name'];
					
					if (isset($files[$name]))
					{
						$_installed[$type][$name] = array_merge($files[$name], $row);
					}
				}
			}
		}
		
		return $_installed[$type];
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Is package
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function is_package($name)
	{
		$this->get_files('');	// blank key lets us skip first party
		return array_key_exists($name, $this->_packages);
	}
}
// END Addons class

/* End of file Addons.php */
/* Location: ./system/expressionengine/libraries/Addons.php */