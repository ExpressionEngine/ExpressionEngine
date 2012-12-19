<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------


// Some of the functions we need - such as updating
// new config files are already in the main app.
// Instead of reimplementing those methods, we'll
// include that file and subclass it again.

require_once(EE_APPPATH.'core/EE_Config'.EXT);

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Config Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Installer_Config Extends EE_Config {
	
	var $config_path 		= ''; // Set in the constructor below
	var $database_path		= ''; // Set in the constructor below
	var $exceptions	 		= array();	 // path.php exceptions
	
	/**
	 * Constructor
	 */	
	public function __construct()
	{	
		parent::__construct();

		$this->config_path		= EE_APPPATH.'/config/config'.EXT;
		$this->database_path	= EE_APPPATH.'/config/database'.EXT;

		$this->_initialize();
	}
	
	// --------------------------------------------------------------------

	/**
	 * Load the EE config file and set the initial values
	 *
	 * @access	private
	 * @return	void
	 */
	function _initialize()
	{
		// Fetch the config file
		if ( ! @include($this->config_path))
		{
			show_error('Unable to locate your config file (expressionengine/config/config.php)');
		}

		// Prior to 2.0 the config array was named $conf.  This has changed to $config for 2.0
		if (isset($conf))
		{
			$config = $conf;
		}
		
		// Is the config file blank?  If not, we bail out since EE hasn't been installed
		if ( ! isset($config) OR count($config) == 0)
		{
			return FALSE;
		}

		// Add the EE config data to the master CI config array
		foreach ($config as $key => $val)
		{
			$this->set_item($key, $val);
		}
		unset($config);

		// Set any config overrides.  These are the items that used to be in 
		// the path.php file, which are now located in the main index file
		$this->_set_overrides($this->config);
		$this->set_item('enable_query_strings', TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Set configuration overrides
	 *
	 * 	These are configuration exceptions.  In some cases a user might want
	 * 	to manually override a config file setting by adding a variable in
	 * 	the index.php or path.php file.  This loop permits this to happen.
	 *
	 * @access	private
	 * @return	void
	 */
	function _set_overrides($params = array())
	{
		if ( ! is_array($params) OR count($params) == 0)
		{
			return;
		}
		
		// Assign global variables if they exist
		$this->_global_vars = ( ! isset($params['global_vars']) OR ! is_array($params['global_vars'])) ? array() : $params['global_vars'];
		
		$exceptions = array();	
		foreach (array('site_url', 'site_index', 'site_404', 'template_group', 'template') as $exception)
		{
			if (isset($params[$exception]) AND $params[$exception] != '')
			{
				if ( ! defined('REQ') OR REQ != 'CP')
				{
					$this->config[$exception] = $params[$exception]; // User/Action
				}
				else
				{
					$exceptions[$exception] = $params[$exception];  // CP
				}				
			}
		}
		
		$this->exceptions = $exceptions;

		unset($params);
		unset($exceptions);
	}

	// --------------------------------------------------------------------

	/**
	 * Get config file - Old version, used by updates leading up to 2.0
	 *
	 * Loads the "container" view file and sets the content
	 *
	 * @access	private
	 * @return	void
	 */	
	function _get_config_1x($preference = '')
	{
		$this->EE =& get_instance();

		if (isset($this->config))
		{
			$table_name = $this->config['db_prefix'].'_sites';
		}
		else
		{
			$table_name = 'exp_sites';
		}
		
		// Preferences table won't exist pre-1.6
		if ( ! $this->EE->db->table_exists($table_name))
		{
			return;
		}
		
		$query = $this->EE->db->query("SELECT `site_system_preferences` FROM $table_name WHERE site_id = '1'");

		$all_preferences = unserialize($query->row('site_system_preferences'));

		// if no specific preference was asked for, return the whole array
		if ($preference == '')
		{
			return $all_preferences;
		}
		else
		{
			return $all_preferences[$preference];
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Update config file - Old version, used by updates leading up to 2.0
	 *
	 * Loads the "container" view file and sets the content
	 *
	 * @access	private
	 * @return	void
	 */	
	function _update_config_1x($newdata = array(), $return_loc = FALSE, $remove_values = array())
	{
		if ( ! is_array($newdata) AND count($remove_values) == 0)
		{
			return FALSE;
		}
		
		require $this->config_path;
		
		// Add new data values to config file		
		if (count($newdata) > 0)
		{
			foreach ($newdata as $key => $val)
			{
				$val = str_replace("\n", " ", $val);
			
				if (isset($config[$key]))
				{			
					$config[$key] = trim($val);	
				}
			}			
		}
		
		// Remove values if needed
		if (is_array($remove_values) AND count($remove_values) > 0)
		{
			foreach ($remove_values as $val)
			{
				unset($config[$val]);
			}
		}
		
		reset($config);
				
		// Write config file as a string
		$new  = "<?php if ( ! defined('BASEPATH')) exit('Invalid file request');\n\n";
	 
		foreach ($config as $key => $val)
		{
			$val = str_replace("\\\"", "\"", $val);
			$val = str_replace("\\'", "'", $val);			
			$val = str_replace('\\\\', '\\', $val);
		
			$val = str_replace('\\', '\\\\', $val);
			$val = str_replace("'", "\\'", $val);
			$val = str_replace("\"", "\\\"", $val);

			$new .= "\$config['".$key."'] = \"".$val."\";\n";
		} 
		
		$new .= '?'.'>';
		
		//  Write config file
		if ($fp = @fopen($this->config_path, FOPEN_WRITE_CREATE_DESTRUCTIVE))
		{
			flock($fp, LOCK_EX);
			fwrite($fp, $new, strlen($new));
			flock($fp, LOCK_UN);
			fclose($fp);
		}
		
		if ($return_loc !== FALSE)
		{		
			$override = ($this->EE->input->get('class_override') != '') ? AMP.'class_override='.$this->EE->input->get_post('class_override') : '';
		
			$this->EE->functions->redirect($return_loc.$override);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Append config file - Old version, used by updates leading up to 2.0
	 *
	 *  This function allows us to add new config file elements
	 *
	 * @access	private
	 * @param	array
	 * @param	array
	 * @return	void
	 */			
	function _append_config_1x($new_config, $unset = array())
	{
		require $this->config_path;

		// Prior to 2.0 the config array was named $conf.  This has changed to $config for 2.0
		if (isset($conf))
		{
			$config = $conf;
		}
				
		if ( ! isset($config))
		{
			return FALSE;
		}

		if ( ! is_array($new_config))
		{
			return FALSE;
		}
		
		// Merge new data to the congig file		
		$config = array_merge($config, $new_config);		
	
	
		// Do we need to remove items?
		if (is_array($unset) AND count($unset) > 0)
		{
			foreach ($unset as $kill)
			{
				if (isset($config[$kill]))
				{
					unset($config[$kill]);
				}
			}
		}
	
		// Build the config string
		$new  = "<?php if ( ! defined('BASEPATH')) exit('Invalid file request');\n\n";
	 
		foreach ($config as $key => $val)
		{
			$val = str_replace("\\'", "'", $val);
			$val = str_replace('\\', '\\\\', $val);
			$val = str_replace("\"", "\\\"", $val);
		
			$new .= "\$config['".$key."'] = \"".$val."\";\n";
		} 
		
		$new .= '?'.'>';
				
				
		// Write the file
		if ($fp = @fopen($this->config_path, FOPEN_WRITE_CREATE_DESTRUCTIVE))
		{
			flock($fp, LOCK_EX);
			fwrite($fp, $new, strlen($new));
			flock($fp, LOCK_UN);
			fclose($fp);
		}		
	}	
}
// END CLASS

/* End of file Installer_Config.php */
/* Location: ./system/expressionengine/installer/libraries/Installer_Config.php */
