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
 * ExpressionEngine CP CSS Loading Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Css extends CI_Controller {

	/**
	 * _remap function
	 * 
	 * Any CSS file in the view collection
	 *
	 * @access	private
	 */
	function _remap()
	{
		if ($this->input->get('M') == 'cp_global_ext')
		{
			return $this->_cp_global_ext();
		}
		
		$file = 'global';
		$path = '';
		
		if ($this->input->get_post('M') == 'third_party' && $package = $this->input->get_post('package'))
		{
			$package = strtolower($package);
			
			$file = $this->input->get_post('file');
			$path = PATH_THIRD.$package.'/';
			
			// There's a good chance we don't need ci_view_path
			// So try this first
			if (file_exists($path.'css/'.$file.'.css'))
			{
				return $this->_load_css_file($path, $file);
			}
		}
		elseif ($this->input->get_post('M') !== FALSE)
		{
			$file = $this->input->get_post('M');
		}
		
		
		$css_paths = array(
			PATH_CP_THEME.$this->session->userdata('cp_theme').'/',
			PATH_CP_THEME.'default/'
		);

		if ($this->session->userdata('cp_theme') == 'default')
		{
			array_shift($css_paths);
		}
		
		foreach ($css_paths as $a_path)
		{
			$path = $a_path.'css/'.$file.'.css';
			
			if (file_exists($path))
			{
				break;
			}
		}
		
		return $this->_load_css_file($path, $file);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Load CSS File
	 *
	 * @access	public
	 * @param	string		path to the CSS
	 * @param	string		name of the CSS file, sans file extension
	 * @return	void
	 */
	private function _load_css_file($path, $file)
	{
		if ( ! file_exists($path.'css/'.$file.'.css'))
		{
			return FALSE;
		}
		
		$this->output->out_type = 'cp_asset';
		$this->output->enable_profiler(FALSE);

		$this->output->send_cache_headers(filemtime($path), 5184000, $path);

		@header('Content-type: text/css');

		$this->output->set_output(file_get_contents($path.'css/'.$file.'.css'));

		if ($this->config->item('send_headers') == 'y')
		{
			@header('Content-Length: '.strlen($this->output->final_output));
		}
	}


	// ------------------------------------------------------------------------	
	
	/**
	 * Control Panel Global Extension
	 *
	 * @access	public
	 * @return	void
	 */
	function _cp_global_ext()
	{
		$str = '';

		/* -------------------------------------------
		/* 'cp_css_end' hook.
		/*  - Add CSS into a file call at the end of the control panel
		/*  - Added 2.1.2
		*/
			$str = $this->extensions->call('cp_css_end');
		/*
		/* -------------------------------------------*/
		
		$this->output->out_type = 'cp_asset';
		$this->output->set_header("Content-Type: text/css");
		
		$this->output->set_header('Content-Length: '.strlen($str));
		$this->output->set_output($str);		
	}

	// --------------------------------------------------------------------
	
}
// END CLASS

/* End of file css.php */
/* Location: ./system/expressionengine/controllers/cp/css.php */