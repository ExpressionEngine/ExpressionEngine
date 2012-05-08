<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
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
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

class View {

	private $EE;

	protected $_template = 'default';
	protected $_theme = 'default';
	protected $_data = array();
	protected $_disabled = array();
	
	public function __construct()
	{
		$this->EE =& get_instance();
	}
	
	// --------------------------------------------------------------------------
	
	/**
	 * Set Theme
	 */
	public function set_cp_theme($cp_theme)
	{
		if ($cp_theme == 'default')
		{
			return;
		}
		
		$this->_theme = $cp_theme;
		
		$this->EE->session->userdata['cp_theme'] = $cp_theme;
		$this->EE->load->add_theme_cascade(PATH_CP_THEME.$cp_theme.'/');
	}

	// --------------------------------------------------------------------------

	/**
	 * Set Template
	 */
	public function set_template($template = 'default')
	{
		$this->_template = $template;
	}
	
	// --------------------------------------------------------------------------
	
	/**
	 * Render output (html)
	 */
	public function render($view, $data = array(), $return = FALSE)
	{
		// @todo move menu, accessory, and sidebar creation here
	//	$this->_menu();
		$this->_accessories();
	//	$this->_sidebar();


		$this->EE->javascript->compile();
		$this->EE->load->helper('view_helper');

		$data = array_merge($this->_data, $data);
		$data['EE_render_view'] = $view;

		return $this->EE->load->view('_templates/'.$this->_template, $data, $return);
	}

	protected function _accessories()
	{
		if ($this->disabled('accessories'))
		{
			return;
		}

		$this->EE->load->library('accessories');
		$this->_data['cp_accessories'] = $this->EE->accessories->generate_accessories();
	}

	// --------------------------------------------------------------------------

	public function disable()
	{
		$which = func_get_args();
		while ($el = array_pop($which))
		{
			$this->_disabled[] = $el;
		}
	}

	// --------------------------------------------------------------------------

	public function disabled($which)
	{
		return in_array($which, $this->_disabled);
	}

	// --------------------------------------------------------------------------

	/**
	 * Head Title
	 *
	 * This tag generates an HTML Title Tag
	 */
	public function head_title($title)
	{
		return '<title>' . $title . ' | ExpressionEngine</title>'.PHP_EOL;
	}
	
	// --------------------------------------------------------------------------

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
		$src_dir = ($this->EE->config->item('use_compressed_js') == 'n') ? 'src/' : 'compressed/';
		
		$path = PATH_THEMES.'javascript/'.$src_dir.$file;
		
		if ( ! file_exists($path))
		{
			return NULL;
		}
		
		$filemtime = filemtime($path);
		
		$url = $this->EE->config->item('theme_folder_url') . 'javascript/' . $src_dir . $file . '?v=' . $filemtime;
		
		return '<script type="text/javascript" src="' . $url . '"></script>'.PHP_EOL;
	}

	// --------------------------------------------------------------------------
	
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

	// --------------------------------------------------------------------------
	
	/**
	 * Get themes URL from supplied system path
	 *
	 * this function will extract which theme we will be loading the file from.
	 * 
	 * @param 	string	system path of the file.
	 * @return 	string	the URL
	 */
	protected function _get_theme_from_path($path)
	{
		$path = '/'.trim($path, '/');
		
		$theme_name = ltrim(strrchr($path, '/'), '/');

		return $this->EE->config->item('theme_folder_url') . 'cp_themes/' . $theme_name . '/';		
	}

	// --------------------------------------------------------------------------
	
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