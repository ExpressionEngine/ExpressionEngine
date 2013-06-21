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
 * ExpressionEngine Javascript Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Javascript extends CI_Javascript {

	var $global_vars = array();
	
	// --------------------------------------------------------------------

	/**
	 * Set Global
	 *
	 * Add a variable to the EE javascript object.  Useful if you need
	 * to dynamically set variables for your external script.  Will intelligently
	 * resolve namespaces (i.e. filemanager.filelist) - use them.
	 *
	 * @access	public
	 */
	function set_global($var, $val = '')
	{
		if (is_array($var))
		{
			foreach($var as $k => $v)
			{
				$this->set_global($k, $v);
			}
			return;
		}
		
		$sections = explode('.', $var);
		$var_name = array_pop($sections);
		
		$current =& $this->global_vars;
		
		foreach($sections as $namespace)
		{
			if ( ! isset($current[$namespace]))
			{
				$current[$namespace] = array();
			}
			
			$current =& $current[$namespace];
		}
		
		if (is_array($val) && isset($current[$var_name]) && is_array($current[$var_name]))
		{
			$current[$var_name] = ee_array_unique(array_merge($current[$var_name], $val), SORT_STRING);
		}
		else
		{
			$current[$var_name] = $val;
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 * Extending the compile function to add the globals
	 *
	 * @access	public
	 */
	function compile($view_var = 'script_foot', $script_tags = TRUE)
	{
		parent::compile($view_var, $script_tags);
		
		$global_js = $this->inline('
			document.documentElement.className += "js";

			var EE = '.json_encode($this->global_vars).';

			if (typeof console === "undefined" || ! console.log) {
				console = { log: function() { return false; }};
			}
		');

		$this->CI->view->cp_global_js = $global_js;
	}

	// -------------------------------------------------------------------------

	/**
	 * Generate JSON
	 *
	 * Can be passed a database result or associative array and returns a JSON
	 * formatted string
	 * 
	 * @param	mixed	result set or array
	 * @param	bool	match array types (defaults to objects)
	 * @return	string	a json formatted string
	 */
	public function generate_json($result = NULL, $match_array_type = FALSE)
	{
		$EE =& get_instance();
		$EE->load->library('logger');
		$EE->logger->deprecated('2.6', 'the native JSON extension (json_encode())');

		return parent::generate_json($result, $match_array_type);
	}
}

// END EE_Javascript


/* End of file EE_Javascript.php */
/* Location: ./system/expressionengine/libraries/EE_Javascript.php */