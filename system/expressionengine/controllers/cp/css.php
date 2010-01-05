<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/docs/license.html
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


	function Css()
	{
		// Call the Controller constructor.  
		// Without this, the world as we know it will end!
		parent::Controller();

		// Does the "core" class exist?  Normally it's initialized
		// automatically via the autoload.php file.  If it doesn't
		// exist it means there's a problem.
		if ( ! isset($this->core) OR ! is_object($this->core))
		{
			show_error('The ExpressionEngine Core was not initialized.  Please make sure your autoloader is correctly set up.');
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 * Index function
	 * 
	 * Every controller must have an index function, which gets called
	 * automatically by CodeIgniter when the URI does not contain a call to
	 * a specific method call
	 *
	 * @access	public
	 * @return	mixed
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
		$file = ($this->input->get_post('M') !== FALSE) ? $this->input->get_post('M') : 'global';
		$path = $this->load->_ci_view_path.'css/'.$file.'.css';

		if (file_exists($path))
		{
			$this->output->enable_profiler(FALSE);

			if ($this->config->item('send_headers') == 'y')
			{
				$max_age		= 5184000;
				$modified		= filemtime($path);
				$modified_since	= $this->input->server('HTTP_IF_MODIFIED_SINCE');

				// Remove anything after the semicolon

				if ($pos = strrpos($modified_since, ';') !== FALSE)
				{
					$modified_since = substr($modified_since, 0, $pos);
				}

				// Send a custom ETag to maintain a useful cache in
				// load-balanced environments

				header("ETag: ".md5($modified.$path));

				// If the file is in the client cache, we'll
				// send a 304 and be done with it.

				if ($modified_since && (strtotime($modified_since) == $modified))
				{
					$this->output->set_status_header(304);
					exit;
				}

				// All times GMT
				$modified = gmdate('D, d M Y H:i:s', $modified).' GMT';
				$expires = gmdate('D, d M Y H:i:s', time() + $max_age).' GMT';

				$this->output->set_status_header(200);
				@header("Cache-Control: max-age={$max_age}, must-revalidate");
				@header('Vary: Accept-Encoding');
				@header('Last-Modified: '.$modified);
				@header('Expires: '.$expires);
			}
			
			$contents = $this->load->view('css/'.$file.'.css', '', TRUE);
			
			if ($this->config->item('send_headers') == 'y')
			{
				@header('Content-Length: '.strlen($contents));
			}

			@header("Content-type: text/css");
			exit($contents);
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