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
class Css extends Controller {

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function Css()
	{
		parent::Controller();
	}
	
	// --------------------------------------------------------------------

	/**
	 * Index function
	 *
	 * @access	public
	 * @return	void
	 */	
	function index()
	{
		// everything's handled by _remap()
	}
	
	// --------------------------------------------------------------------

	/**
	 * _remap function
	 * 
	 * Any CSS file in the view collection
	 *
	 * @access	private
	 */
	function _remap()
	{
		if ($this->input->get_post('M') == 'third_party' && $package = $this->input->get_post('package'))
		{
			$file = $this->input->get_post('file');
			$this->load->_ci_view_path = PATH_THIRD.$package.'/';
		}
		else
		{
			$file = ($this->input->get_post('M') !== FALSE) ? $this->input->get_post('M') : 'global';
		}
		
		$path = $this->load->_ci_view_path.'css/'.$file.'.css';
		
		if (file_exists($path))
		{
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
			
			$this->output->set_header('Content-type: text/css');
			$this->output->set_header("Cache-Control: max-age={$max_age}, must-revalidate");
			$this->output->set_header('Vary: Accept-Encoding');
			$this->output->set_header('Last-Modified: '.$modified);
			$this->output->set_header('Expires: '.$expires);
			
			
			$this->load->view('css/'.$file.'.css', '');

			if ($this->config->item('send_headers') == 'y')
			{
				$this->output->set_header('Content-Length: '.strlen($this->output->final_output));
			}

		}
		else
		{
			show_404($file.'css');
		}
	}
	
	
}
// END CLASS

/* End of file css.php */
/* Location: ./system/expressionengine/controllers/cp/css.php */