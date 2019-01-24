<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Core Extensions
 */
class EE_Extensions {

	var $extensions 	= array();
	var $end_script		= FALSE;	// To return or not to return
	var $last_call		= FALSE;	// The data returned from the last called method for this hook
	var $in_progress	= '';		// Last hook called.  Prevents loops.
	var $s_cache		= array();	// Array of previously unserialized settings
	var $version_numbers = array(); // To track the version of an extension


	/**
	 * Constructor
	 */
	function __construct()
	{
		// We only execute this if extensions are allowed
		if (ee()->config->item('allow_extensions') == 'y')
		{
			$query = ee()->db->query("SELECT DISTINCT ee.* FROM exp_extensions ee WHERE enabled = 'y' ORDER BY hook, priority ASC, class");

			if ($query->num_rows() > 0)
			{
				$this->extensions = array();

				foreach($query->result_array() as $row)
				{
					// Calls are unique for class & priority. Multiple extensions may
					// use the same hook, with their effects interspersed based on priority.
					// That could even be within a single extension calling different methods
					// on the same hook, but they must have different priorities.
					// If the developer has given them identical priorities in the same extension
					// only the last one in will run.

					// force the classname to conform to standard casing
					$row['class'] = ucfirst(strtolower($row['class']));

					$this->extensions[$row['hook']][$row['priority']][$row['class']] = array($row['method'], $row['settings'], $row['version']);

					$this->version_numbers[$row['class']] = $row['version'];
				}
			}
		}
	}

	/**
	 * Universal caller, was used for php 4 compatibility
	 *
	 * @deprecated 3.0 Use call()
	 */
	function universal_call($which, &$parameter_one)
	{
		ee()->load->library('logger');
		ee()->logger->deprecated('3.0', 'Use extensions->call');

		return call_user_func_array(array($this, 'call'), func_get_args());
	}

	/**
	 * Extension Hook Method
	 *
	 * Used in ExpressionEngine to call an extension based on whichever
	 * hook is being triggered
	 *
	 * @access	public
	 * @param	string	Name of the  extension hook
	 * @param	mixed
	 * @return	mixed
	 */
	function call($which)
	{
		// Reset Our Variables
		$this->end_script = FALSE;
		$this->last_call  = FALSE;

		// Anything to Do Here?
		if ( ! isset($this->extensions[$which]))
		{
			return;
		}

		if (ee()->config->item('allow_extensions') != 'y')
		{
			return;
		}

		if ($this->in_progress == $which)
		{
			return;
		}

		$this->in_progress = $which;

		ee()->load->library('addons');
		ee()->addons->is_package('');

		// Retrieve arguments for function
		$args = array_slice(func_get_args(), 1);

		// Go through all the calls for this hook
		foreach ($this->extensions[$which] as $priority => $calls)
		{
			foreach ($calls as $class => $metadata)
			{
				$this->call_class($class, $which, $metadata, $args);

				// A ee()->extensions->end_script value of TRUE means that the
				// called method wishes us to stop the calling of the main
				// script. In this case, even if there are methods after this
				// one for the hook we still stop the script now because
				// extensions with a higher priority call the shots and thus
				// override any extensions with a lower priority.
				if ($this->end_script === TRUE)
				{
					break;
				}
			}

			// Have to keep breaking since break only accepts parameters as of
			// PHP 5.4.0
			if ($this->end_script === TRUE)
			{
				break;
			}
		}

		$this->in_progress = '';
		return $this->last_call;
	}

	/**
	 * Call an extension on a single item in the extensions array
	 */
	public function call_class($class, $which, $metadata, $args = array())
	{
		ee()->load->library('addons');
		ee()->addons->is_package('');

		// Determine Path of Extension
		$class_name = ucfirst($class);
		$name = ee()->security->sanitize_filename(strtolower(substr($class, 0, -4))); // remove '_ext' suffix

		$path = ee()->addons->_packages[$name]['extension']['path'];
		$extension_path = reduce_double_slashes($path.'/ext.'.$name.'.php');

		// Check to see if we need to automatically load the path
		$automatically_load_path = (array_search($path, ee()->load->get_package_paths()) === FALSE);

		if ($automatically_load_path)
		{
			if ( ! file_exists($extension_path))
			{
				$error = 'Unable to load the following extension file:<br /><br />'.'ext.'.$name.'.php';
				return ee()->output->fatal_error($error);
			}

			ee()->load->add_package_path($path, FALSE);
		}

		// Include File
		if ( ! class_exists($class_name))
		{
			require $extension_path;
		}

		// A Bit of Meta
		$method	= $metadata['0'];

		// Unserializing and serializing is relatively slow, so we
		// cache the settings just in case multiple hooks are calling the
		// same extension multiple times during a single page load.
		// Thus, speeding it all up a bit.
		if (isset($this->s_cache[$class_name]))
		{
			$settings = $this->s_cache[$class_name];
		}
		else
		{
			$settings = ($metadata['1'] == '') ? '' : strip_slashes(unserialize($metadata['1']));
			$this->s_cache[$class_name] = $settings;
		}

		$version = $metadata['2'];


		//  Call the class(s)
		//  Each method could easily have its own settings,
		//  so we have to send the settings each time
		$obj = new $class_name($settings);

		// Update Extension First?
		if (version_compare($obj->version, $this->version_numbers[$class_name], '>') && method_exists($obj, 'update_extension') === TRUE)
		{
			$update = call_user_func(array($obj, 'update_extension'), $this->version_numbers[$class_name]);

			$this->version_numbers[$class_name] = $obj->version;  // reset master
		}

		//  Call Method and Store Returned Data
		//  We put this in a class variable so that any extensions
		//  called after this one can retrieve the returned data from
		//  previous methods and view/maniuplate that returned data
		//  opposed to any original arguments the hook sent. In theory...
		if (isset(ee()->TMPL) && is_object(ee()->TMPL) && method_exists(ee()->TMPL, 'log_item'))
		{
			ee()->TMPL->log_item('Calling Extension Class/Method: '.$class_name.'/'.$method);
		}


		$this->last_call = call_user_func_array(array($obj, $method), $args);

		if ($automatically_load_path)
		{
			ee()->load->remove_package_path($path);
		}
	}

	/**
	 * Get Active Hook Info
	 *
	 * Getter for the $extensions property
	 *
	 * @param	string		name of the extension hook
	 * @return	array|bool	Hook details array or FALSE if not active
	 **/
	public function get_active_hook_info($hook)
	{
		if ( ! $this->active_hook($hook))
		{
			return FALSE;
		}

		return $this->extensions[$hook];
	}

	/**
	 * Active Hook
	 *
	 * Check If Hook Has Activated Extension
	 *
	 * @access	public
	 * @param	string	Name of the  extension hook
	 * @return	bool
	 */
	function active_hook($which)
	{
		// Hop out if extensions are disabled
		if (ee()->config->item('allow_extensions') != 'y') return FALSE;

		return (isset($this->extensions[$which])) ? TRUE : FALSE;
	}

}
// END CLASS

// EOF
