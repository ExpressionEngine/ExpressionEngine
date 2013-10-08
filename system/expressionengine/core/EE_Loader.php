<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
 * Loader Class
 *
 * Loads views and files
 *
 * @package		CodeIgniter
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Loader extends CI_Loader {

	public $_ci_view_path = ''; // deprecated, do not change, was private in 2.1.5 and will be private again in the near future
	private $ee_view_depth = 0;


	/**
	 * Load CI View
	 *
	 * This is extended to keep some backward compatibility for people
	 * changing _ci_view_path. I tried doing a getter/setter, but since all
	 * of CI's object references are stuck onto the loader when loading views
	 * I get access errors left and right. -pk
	 *
	 * @deprecated 2.6
	 */
	public function view($view, $vars = array(), $return = FALSE)
	{
		if ($this->ee_view_depth === 0 && $this->_ci_view_path != '')
		{
			ee()->load->library('logger');
			ee()->logger->deprecated('2.6', 'load::add_package_path()');

			$this->_ci_view_paths = array($this->_ci_view_path => FALSE) + $this->_ci_view_paths;
		}

		if (is_array($vars) && isset($vars['cp_page_title']))
		{
			ee()->view->cp_page_title = $vars['cp_page_title'];
		}

		$this->ee_view_depth++;

		$ret = parent::view($view, $vars, $return);

		$this->ee_view_depth--;

		if ($this->ee_view_depth === 0 && $this->_ci_view_path != '')
		{
			array_shift($this->_ci_view_paths);
		}

		return $ret;
	}

	// ------------------------------------------------------------------------

	/**
	 * Load EE View
	 *
	 * This is for limited use inside packages. It loads from EE's main cp
	 * theme folder and ignores the package's view folder. The main reason
	 * for doing this are layout things, like the glossary. Most developers
	 * will not need this. -pk
	 *
	 * @param	string
	 * @param	array 	variables to be loaded into the view
	 * @param	bool 	return or not
	 * @return	void
	 */
	public function ee_view($view, $vars = array(), $return = FALSE)
	{
		$ee_only = array();
		$orig_paths = $this->_ci_view_paths;

		// Regular themes cascade down to the first
		// path (APPPATH.'views'), so we copy them over
		// until we hit a third party or non_cascading path.

		foreach (array_reverse($orig_paths, TRUE) as $path => $cascade)
		{
			if (strpos($path, PATH_THIRD) !== FALSE OR $cascade === FALSE)
			{
				break;
			}

			$ee_only[$path] = TRUE;
		}

		// Temporarily replace them, load the view, and back again
		$this->_ci_view_paths = array_reverse($ee_only, TRUE);

		$ret = $this->view($view, $vars, $return);

		$this->_ci_view_paths = $orig_paths;

		return $ret;
	}

	// ------------------------------------------------------------------------

	/**
	 * $this->load->library('security') is deprecated as the CI_Security
	 * class has been moved to Core, so it is always loaded.  In order to ease
	 * the transition for third-party developers, we are extending the CI
	 * loader function to return NULL in the event a load function to Security
	 * is called.
	 *
	 * $this->load->library('security') is @deprecated, and this temporary
	 * workaround will be removed in a future version
	 */
	public function library($library = '', $params = NULL, $object_name = NULL)
	{
		if (is_array($library))
		{
			foreach($library as $read)
			{
				$this->library($read);
			}

			return;
		}

		if (strtolower($library) == 'security')
		{
			ee()->load->library('logger');
			ee()->logger->deprecated('2.6', 'The security library is always loaded.');
			return NULL;
		}

		return parent::library($library, $params, $object_name);
	}

	// ------------------------------------------------------------------------

	/**
	 * Add to the theme cascading
	 *
	 * Adds a theme to cascade down to. You probably don't
	 * need to call this. No really, don't.
	 */
	public function add_theme_cascade($theme_path)
	{
		$this->_ci_view_paths = array($theme_path => TRUE) + $this->_ci_view_paths;
	}

	// ------------------------------------------------------------------------

	/**
	 * Get top of package path
	 *
	 * We use this to allow package js/css loading, where we need to figure out
	 * a theme name. May be renamed in the future, don't use it.
	 */
	public function first_package_path()
	{
		reset($this->_ci_view_paths);
		return key($this->_ci_view_paths);
	}
}

/* End of file EE_Loader.php */
/* Location: ./system/expressionengine/core/EE_Loader.php */
