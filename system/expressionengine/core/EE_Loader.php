<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
	
	private $_ee_view_paths = array();
	
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
		// The only way to get a string view_path is to
		// either set directly or to call a nested view.
		// If the path isn't in the originals we'll can
		// assume that it was set on purpose.
		
		// This means that modules can't set the view path
		// to an array, which is fine for now. Once we merge
		// reactor's view changes we can fix that.
		
		if (is_string($this->_ci_view_path) &&
			! in_array($this->_ci_view_path, $this->_ee_view_paths))
		{
			return parent::view($view, $vars, $return);
		}
		
		// Non-module load call? Reset the paths so that themes
		// can cascade in nested views.
		
		$paths = $this->_ee_view_paths;
		
		if (count($paths) > 1)
		{
			$ext = pathinfo($view, PATHINFO_EXTENSION);
			$ext = $ext ? '' : EXT;
			
			foreach ($paths as $path)
			{
				if (file_exists($path.$view.$ext))
				{
					$this->_ci_view_path = $path;
					break;
				}
			}
		}
		else
		{
			$this->_ci_view_path = $paths[0];
		}

		return parent::view($view, $vars, $return);
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
			return NULL;
		}
		
		return parent::library($library, $params, $object_name);
	}
	
	// ------------------------------------------------------------------------	
	
	/**
	 * Reset Views to the original
	 *
	 * Utility function that resets and returns the original
	 * view path array. Currently only used for glossary views.
	 */
	public function reset_view_path()
	{
		return $this->_ci_view_path = $this->_ee_view_paths;
	}

	// ------------------------------------------------------------------------	
	
	/**
	 * DO NOT CALL THIS
	 *
	 * It's used once - in core.php to setup the initial
	 * view array. After that you should consider it sealed.
	 * If you need to change a view path set it directly or
	 * make your own method! This one is sacred.
	 */
	public function init_orig_views($theme_path = NULL)
	{
		if ( ! empty($this->_ee_view_paths))
		{
			return FALSE;
		}
		
		$this->_ci_view_path = array(APPPATH.'views/');
		
		if ($theme_path)
		{
			array_unshift($this->_ci_view_path, $theme_path);
		}
		
		// store the original
		$this->_ee_view_paths = $this->_ci_view_path;
	}
	
}

/* End of file EE_Loader.php */
/* Location: ./system/expressionengine/core/EE_Loader.php */