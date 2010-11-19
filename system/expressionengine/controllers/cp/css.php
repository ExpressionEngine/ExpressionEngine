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
		$path = $this->load->_ci_view_path;
		
		if ($this->input->get_post('M') == 'third_party' && $package = $this->input->get_post('package'))
		{
			$file = $this->input->get_post('file');
			$path = PATH_THIRD.$package.'/';
		}
		elseif ($this->input->get_post('M') !== FALSE)
		{
			$file = $this->input->get_post('M');
		}

		if (is_array($this->load->_ci_view_path))
		{
			foreach($this->load->_ci_view_path as $path)
			{
				if (file_exists($path.'css/'.$file.'.css'))
				{
					break;
				}
			}
		}
		
		$this->_load_css_file($path, $file);
	}


	private function _load_css_file($path, $file)
	{
		if ( ! file_exists($path.'css/'.$file.'.css'))
		{
			return FALSE;
		}
		
		$this->load->_ci_view_path = $path;
		
		$this->output->out_type = 'cp_asset';
		$this->output->enable_profiler(FALSE);

		$max_age		= 5184000;
		$modified		= filemtime($path);
		$modified_since	= $this->input->server('HTTP_IF_MODIFIED_SINCE');

		// Remove anything after the semicolon

		if ($pos = strrpos($modified_since, ';') !== FALSE)
		{
			$modified_since = substr($modified_since, 0, $pos);
		}

		// If the file is in the client cache, we'll
		// send a 304 and be done with it.

		if ($modified_since && (strtotime($modified_since) == $modified))
		{
			$this->output->set_status_header(304);
			exit;
		}

		// Send a custom ETag to maintain a useful cache in
		// load-balanced environments

		$this->output->set_header("ETag: ".md5($modified.$path));


		// All times GMT
		$modified = gmdate('D, d M Y H:i:s', $modified).' GMT';
		$expires  = gmdate('D, d M Y H:i:s', time() + $max_age).' GMT';

		$this->output->set_status_header(200);

		@header('Content-type: text/css');
		@header("Cache-Control: max-age={$max_age}, must-revalidate");
		@header('Vary: Accept-Encoding');
		@header('Last-Modified: '.$modified);
		@header('Expires: '.$expires);

		$this->load->view('css/'.$file.'.css', '');

		if ($this->config->item('send_headers') == 'y')
		{
			@header('Content-Length: '.strlen($this->output->final_output));
		}
	}


	// ------------------------------------------------------------------------	
	
	/**
	 *
	 *
	 *
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
	
}
// END CLASS

/* End of file css.php */
/* Location: ./system/expressionengine/controllers/cp/css.php */