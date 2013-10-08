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
 * ExpressionEngine Config Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class View {

	private $EE;

	protected $_theme = 'default';
	protected $_extend = '';
	protected $_data = array();
	protected $_disabled = array();
	protected $_disable_up = array();
	
	/**
	 * Constructor
	 *
	 * @access public
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Set Theme
	 *
	 * @access public
	 * @return void
	 */
	public function set_cp_theme($cp_theme)
	{
		if ($cp_theme == 'default')
		{
			return;
		}
		
		$this->_theme = $cp_theme;
		
		ee()->session->userdata['cp_theme'] = $cp_theme;
		ee()->load->add_theme_cascade(PATH_CP_THEME.$cp_theme.'/');
	}

	// --------------------------------------------------------------------
	
	/**
	 * Render output (html)
	 *
	 * @access public
	 * @return void
	 */
	public function render($view, $data = array(), $return = FALSE)
	{
		ee()->load->helper('view_helper');
		ee()->javascript->compile();

		$data = array_merge($this->_data, $data);

		// load up the inner
		$rendered_view = ee()->load->view($view, $data, TRUE);

		// traverse up the extensions
		// we stop passing other data - it's cached in the loader
		while ($this->_extend)
		{
			$view = $this->_extend;
			$this->_extend = '';
			$this->disable($this->_disable_up);
			$rendered_view = ee()->load->view($view, array('EE_rendered_view' => $rendered_view), TRUE);
		}

		// clear for future calls
		$this->_clear();

		if ($return)
		{
			return $rendered_view;
		}

		ee()->output->set_output($rendered_view);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Extend a template or view
	 *
	 * @access public
	 * @return void
	 */
	public function extend($which, $disable)
	{
		$this->_extend = $which;

		if ( ! is_array($disable))
		{
			$disable = array($disable);
		}

		$this->_disable_up = $disable;
	}

	// --------------------------------------------------------------------

	/**
	 * Disable a view feature
	 *
	 * @access public
	 * @return void
	 */
	public function disable($which)
	{
		if ( ! is_array($which))
		{
			$which = array($which);
		}

		while ($el = array_pop($which))
		{
			$this->_disabled[] = $el;
		}

		$this->_disable_up = array();
	}

	// --------------------------------------------------------------------

	/**
	 * Check if a view featuer is disabled
	 *
	 * @access public
	 * @return void
	 */
	public function disabled($which)
	{
		return in_array($which, $this->_disabled);
	}

	// --------------------------------------------------------------------

	/**
	 * Head Title
	 *
	 * This tag generates an HTML Title Tag
	 */
	public function head_title($title)
	{
		return '<title>' . $title . ' | ExpressionEngine</title>'.PHP_EOL;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Script tag
	 *
	 * This function will return a script tag for use the control panel.  It will 
	 * include a v= query string with the filemtime, so farfuture expires headers
	 * can be sent 
	 *
	 * @param 	string		Javascript File, relative to themes/javascript/<src/compressed>/jquery
	 * @return 	string 		script tag
	 */
	public function script_tag($file)
	{
		$src_dir = (ee()->config->item('use_compressed_js') == 'n') ? 'src/' : 'compressed/';
		
		$path = PATH_THEMES.'javascript/'.$src_dir.$file;
		
		if ( ! file_exists($path))
		{
			return NULL;
		}
		
		$filemtime = filemtime($path);
		
		$url = ee()->config->item('theme_folder_url') . 'javascript/' . $src_dir . $file . '?v=' . $filemtime;
		
		return '<script type="text/javascript" src="' . $url . '"></script>'.PHP_EOL;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Head Link
	 *
	 * This function will produce a URL to a css stylesheet, and include the filemtime() so
	 * far-future expires headers can be sent on CSS by the user.
	 *
	 * @param 	string		CSS file, relative to the themes/cp_themes/<theme> directory.
	 * @param	string		produces "media='screen'" by default
	 * @return 	string		returns the link string.	
	 */
	public function head_link($file, $media = 'screen')
	{
		$filemtime = NULL;
		$file_url  = NULL;
		
		$css_paths = array(
			PATH_CP_THEME.$this->_theme.'/',
			PATH_CP_THEME.'default/'
		);
		
		if ($this->_theme == 'default')
		{
			array_shift($css_paths);
		}
				
		foreach($css_paths as $path)
		{
			if (file_exists($path.$file))
			{
				$filemtime = filemtime($path.$file);
				$file_url = $this->_get_theme_from_path($path) . $file;
				break;
			}
		}

		if ($file_url === NULL)
		{
			return NULL;
		}

		return '<link rel="stylesheet" href="'.$file_url.'?v='.$filemtime.'" type="text/css" media="'.$media.'" />'.PHP_EOL;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Get themes URL from supplied system path
	 *
	 * this function will extract which theme we will be loading the file from.
	 * 
	 * @access protected
	 * @param 	string	system path of the file.
	 * @return 	string	the URL
	 */
	protected function _get_theme_from_path($path)
	{
		$path = '/'.trim($path, '/');
		
		$theme_name = ltrim(strrchr($path, '/'), '/');

		return ee()->config->item('theme_folder_url') . 'cp_themes/' . $theme_name . '/';		
	}

	// --------------------------------------------------------------------
	
	/**
	 * Clear the class
	 *
	 * @access protected
	 * @return void
	 */
	protected function _clear()
	{
		$this->_extend = '';
		$this->_data = array();
		$this->_disabled = array();
		$this->_disable_up = array();
	}

	// --------------------------------------------------------------------
	// Template Data Getters and Setters
	// --------------------------------------------------------------------

	public function __set($key, $value)
	{
		$this->_data[$key] = $value;
	}
	
	public function __get($key)
	{
		return isset($this->_data[$key]) ? $this->_data[$key] : NULL;
	}

	public function __isset($key)
	{
		return isset($this->_data[$key]);
	}

	public function __unset($key)
	{
		unset($this->_data[$key]);
	}
}