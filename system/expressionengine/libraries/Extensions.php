<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Core Extensions Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Extensions {

	var $extensions 	= array();
	var $OBJ			= array();	// Current Instantiated Object
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
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

		// We only execute this if extensions are allowed
		if (ee()->config->item('allow_extensions') == 'y')
		{
			$query = ee()->db->query("SELECT DISTINCT ee.* FROM exp_extensions ee WHERE enabled = 'y' ORDER BY hook, priority ASC, class");

			if ($query->num_rows() > 0)
			{
				$this->extensions = array();

				foreach($query->result_array() as $row)
				{
					// There is a possiblity that there will be three extensions for a given
					// hook and that two of them will call the same class but different methods
					// while the third will have a priority that places it between those two.
					// The chance is pretty remote and I cannot think offhand why someone
					// would do this, but I have learned that our users and developers are
					// a crazy bunch so I should make shite like this work initially and not
					// just fix it later.

					// However, it makes no sense for a person to call the same class but different
					// methods for the same hook at the same priority.  I feel confident in this.
					// If someone does do this I will just have to point out the fact that they
					// are a complete nutter.

					// force the classname to conform to standard casing
					$row['class'] = ucfirst(strtolower($row['class']));

					$this->extensions[$row['hook']][$row['priority']][$row['class']] = array($row['method'], $row['settings'], $row['version']);

					$this->version_numbers[$row['class']] = $row['version'];
				}
			}
		}
	}

	// --------------------------------------------------------------------

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
	function call($which, $parameter_one='')
	{
		// Reset Our Variables
		$this->end_script	= FALSE;
		$this->last_call	= FALSE;

		// A Few Checks
		if ( ! isset($this->extensions[$which])) return;
		if (ee()->config->item('allow_extensions') != 'y') return;
		if ($this->in_progress == $which) return;

		// Get Arguments, Call the New Universal Method
		$args = func_get_args();

		if (count($args) == 1)
		{
			$args = array($which, '');
		}

		if (is_php('5.3'))
		{
			foreach ($args as $k => $v)
			{
				$args[$k] =& $args[$k];
			}
		}

		return call_user_func_array(array(&$this, 'universal_call'), $args);
	}

	// --------------------------------------------------------------------

	/**
	 * The Universal Caller (Added in EE 1.6)
	 *
	 *  Originally, using call(), objects could not be called by reference in PHP 4
	 *  and thus could not be directly modified.  I found a clever way around that restriction
	 *  by always having the second argument gotten by reference.  The problem (and the reason
	 *  there is a call() hook above) is that not all extension hooks have a second argument
	 *  and the PHP developers in their infinite wisdom decided that only variables could be passed
	 *  by reference.  So, call() does a little magic to make sure there is always a second
	 *  argument and universal_call() handles all of the object and reference handling
	 *  when needed.  -Paul
	 *
	 * @access	public
	 * @param	string	Name of the  extension hook
	 * @param	mixed
	 * @return	mixed
	 */
	function universal_call($which, &$parameter_one)
	{
		// Reset Our Variables
		$this->end_script	= FALSE;
		$this->last_call	= FALSE;
		$php5_args			= array();

		// Anything to Do Here?
		if ( ! isset($this->extensions[$which])) return;
		if (ee()->config->item('allow_extensions') != 'y') return;
		if ($this->in_progress == $which) return;

		$this->in_progress = $which;
		ee()->load->library('addons');
		ee()->addons->is_package('');

		// Retrieve arguments for function
		if (is_object($parameter_one) && is_php('5.0.0') == TRUE)
		{
			$php4_object = FALSE;
			$args = array_slice(func_get_args(), 1);
		}
		else
		{
			$php4_object = TRUE;
			$args = array_slice(func_get_args(), 1);
		}

		if (is_php('5'))
		{
			foreach($args as $k => $v)
			{
				$php5_args[$k] =& $args[$k];
			}
		}


		// Give arguments by reference
		foreach($args as $k => $v)
		{
			$args[$k] =& $args[$k];
		}

		// Go through all the calls for this hook
		foreach($this->extensions[$which] as $priority => $calls)
		{
			foreach($calls as $class => $metadata)
			{
				// Determine Path of Extension
				$class_name = ucfirst($class);
				$name = ee()->security->sanitize_filename(strtolower(substr($class, 0, -4))); // remove '_ext' suffix

				$path = ee()->addons->_packages[$name]['extension']['path'];
				$extension_path = reduce_double_slashes($path.'/ext.'.$name.'.php');

				if (file_exists($extension_path))
				{
					ee()->load->add_package_path($path, FALSE);
				}

				else
				{
					$error = 'Unable to load the following extension file:<br /><br />'.'ext.'.$name.'.php';
					return ee()->output->fatal_error($error);
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
				$this->OBJ[$class_name] = new $class_name($settings);

				// Update Extension First?
				if (version_compare($this->OBJ[$class_name]->version, $this->version_numbers[$class_name], '>') && method_exists($this->OBJ[$class_name], 'update_extension') === TRUE)
				{
					$update = call_user_func_array(array(&$this->OBJ[$class_name], 'update_extension'), array($this->version_numbers[$class_name]));

					$this->version_numbers[$class_name] = $this->OBJ[$class_name]->version;  // reset master
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

				if ($php4_object === TRUE)
				{
					$this->last_call = call_user_func_array(array(&$this->OBJ[$class_name], $method), array(&$parameter_one) + $args);
				}
				elseif ( ! empty($php5_args))
				{
					$this->last_call = call_user_func_array(array(&$this->OBJ[$class_name], $method), $php5_args);
				}
				else
				{
					$this->last_call = call_user_func_array(array(&$this->OBJ[$class_name], $method), $args);
				}

				$this->in_progress = '';


				ee()->load->remove_package_path($path);

				//  A ee()->extensions->end_script value of TRUE means that the called
				//	method wishes us to stop the calling of the main script.
				//  In this case, even if there are methods after this one for
				//  the hook we still stop the script now because extensions with
				//  a higher priority call the shots and thus override any
				//  extensions with a lower priority.
				if ($this->end_script === TRUE) return $this->last_call;
			}
		}

		return $this->last_call;
	}

	// --------------------------------------------------------------------

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

	// --------------------------------------------------------------------

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

/* End of file Extensions.php */
/* Location: ./system/expressionengine/libraries/Extensions.php */
