<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
 * Loader Class
 *
 * Loads views and files
 *
 * @package		CodeIgniter
 * @subpackage	Core
 * @category	Core
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class EE_Loader extends CI_Loader {
	
	/**
	 * Load View
	 *
	 * This extended function exists to provide overloading of themes.  It's silly and
	 * difficult trying to maintain multiple themes that are just tweaks to CSS and images.
	 * Brandon is a smart cookie for thinking this idea up.  -ga
	 *
	 * @param	string		
	 * @param	array 	variables to be loaded into the view
	 * @param	bool 	return or not
	 * @return	void
	 */
	public function view($view, $vars = array(), $return = FALSE)
	{
		$paths = $this->_ci_view_path;

		if (is_array($this->_ci_view_path))
		{
			$ext = pathinfo($view, PATHINFO_EXTENSION);
			$ext = $ext ? '' : EXT;
			
			foreach ($this->_ci_view_path as $path)
			{
				if (file_exists($path.$view.$ext))
				{
					$this->_ci_view_path = $path;
					break;
				}
			}
		}

		$ret = parent::view($view, $vars, $return);
		
		$this->_ci_view_path = $paths;
		return $ret;
	}
}

/* End of file EE_Loader.php */
/* Location: ./system/expressionengine/core/EE_Loader.php */