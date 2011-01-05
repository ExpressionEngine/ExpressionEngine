<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
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
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
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
			$current[$var_name] = array_unique(array_merge($current[$var_name], $val));
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
		// Experimental reduction of inline js
		/*
		foreach($this->js->jquery_code_for_compile as $k => $v)
		{
			// Remove Comments
			$v = preg_replace('/\/\/[^\n\r\'";{}]*[\n\r]/', ' ', $v);
			$v = preg_replace('/\/\*[^*]*\*+([^\/\'";{}][^*]*\*+)*\//', ' ', $v);
			
			// Safe Whitespace Replacements
			$v = preg_replace('/[\t ]+/', ' ', $v);
			$v = preg_replace('/(\s*?\n+\s*?)+/', "\n", $v);
			
			// Common Ones
			$v = preg_replace('/([;]\n|\) \{|\{\n)/', '', $v);
			
			$this->js->jquery_code_for_compile[$k] = $v;
		}
		*/
		
		parent::compile($view_var, $script_tags);
		
		$global_js = $this->inline('
			document.documentElement.className += "js";

			if (typeof console === "undefined" || ! console.log) {
				console = { log: function() { return false; }};
			}
			
			if (typeof EE === "undefined" || ! EE) {
				var EE = '.$this->generate_json($this->global_vars).';
			}
		');

		$this->CI->cp->set_variable('cp_global_js', $global_js);
	}
}

// END EE_Javascript


/* End of file EE_Javascript.php */
/* Location: ./system/expressionengine/libraries/EE_Javascript.php */