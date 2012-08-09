<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Javascript Loading Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://expressionengine.com
 */
class Javascript extends CI_Controller {

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		$this->load->library('core');
		$this->core->bootstrap();

		$dir = ($this->config->item('use_compressed_js') == 'n') ? 'src' : 'compressed';
		define('PATH_JAVASCRIPT', PATH_THEMES.'javascript/'.$dir.'/');

		$this->lang->loadfile('jquery');
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
		// use view->script_tag() instead
		// $this->load('jquery');
	}

	// --------------------------------------------------------------------

	/**
	 * Spellcheck iFrame
	 *
	 * Used by the Spellcheck crappola
	 *
	 * @access	public
	 * @return	void
	 */
	function spellcheck_iframe()
	{
		$this->output->enable_profiler(FALSE);
		
		if ( ! class_exists('EE_Spellcheck'))
		{
			require APPPATH.'libraries/Spellcheck.php'; 
		}

		return EE_Spellcheck::iframe();
	}

	// --------------------------------------------------------------------

	/**
	 * Spellcheck
	 *
	 * Used by the Spellcheck crappola
	 *
	 * @access	public
	 * @return	void
	 */
	function spellcheck()
	{
		$this->output->enable_profiler(FALSE);

		if ( ! class_exists('EE_Spellcheck'))
		{
			require APPPATH.'libraries/Spellcheck.php'; 
		}

		return EE_Spellcheck::check();
	}

	// --------------------------------------------------------------------

	/**
	 * Load
	 *
	 * Sends jQuery files to the browser
	 *
	 * @access	public
	 * @return	type
	 */
	function load($loadfile = '')
	{
		$this->output->enable_profiler(FALSE);
		
		$file = '';		
		$cp_theme = $this->input->get_post('theme');
		$package = $this->input->get_post('package');

		// trying to load a specific js file?
		$loadfile = $this->input->get_post('file');
		$loadfile = $this->security->sanitize_filename($loadfile, TRUE);
		
		if ($loadfile == 'ext_scripts')
		{
			return $this->_ext_scripts();
		}
		
		if ($package && $loadfile)
		{
			$file = PATH_THIRD.$package.'/javascript/'.$loadfile.'.js';
		}
		elseif ($loadfile == '')
		{
			if (($plugin = $this->input->get_post('plugin')) !== FALSE)
			{
				$file = PATH_JAVASCRIPT.'jquery/plugins/'.$plugin.'.js';
			}
			elseif (($ui = $this->input->get_post('ui')) !== FALSE)
			{
				$file = PATH_JAVASCRIPT.'jquery/ui/jquery.ui.'.$ui.'.js';
			}
		}
		else
		{
			$file = PATH_JAVASCRIPT.$loadfile.'.js';
		}

		if ( ! $file OR ! file_exists($file))
		{
			if ($this->config->item('debug') >= 1)
			{
				$this->output->fatal_error(lang('missing_jquery_file'));
			}
			else
			{
				return FALSE;
			}
		}

		// Can't do any of this if we're not allowed
		// to send any headers

		$this->_set_headers($file);

		// Grab the file, content length and serve
		// it up with the proper content type!

		$contents = file_get_contents($file);

		$this->output->set_header('Content-Length: '.strlen($contents));
		$this->output->set_output($contents);
	}
	
	// --------------------------------------------------------------------	

	/**
	 * Javascript from extensions
	 *
	 * This private method is intended for usage by the 'add_global_cp_js' hook 
	 *
	 * @access 	private
	 * @return 	void
	 */
	function _ext_scripts()
	{
		$str = '';

		/* -------------------------------------------
		/* 'cp_js_end' hook.
		/*  - Add Javascript into a file call at the end of the control panel
		/*  - Added 2.1.2
		*/
			$str = $this->extensions->call('cp_js_end');
		/*
		/* -------------------------------------------*/
		
		$this->output->out_type = 'cp_asset';
		$this->output->set_header("Content-Type: text/javascript");
		
		$this->output->set_header('Content-Length: '.strlen($str));
		$this->output->set_output($str);
	}


	// --------------------------------------------------------------------

	/**
	 * Javascript Combo Loader 
	 *
	 * Combo load multiple javascript files to reduce HTTP requests
	 * BASE.AMP.'C=javascript&M=combo&ui=ui,packages&file=another&plugin=plugins&package=third,party,packages'
	 * 
	 * @access public
	 * @return string
	 */
	function combo_load()
	{
		$this->output->enable_profiler(FALSE);

		$contents	= '';
		$types		= array(
			'ui'		=> PATH_JAVASCRIPT.'jquery/ui/jquery.ui.',
			'plugin'	=> PATH_JAVASCRIPT.'jquery/plugins/',
			'file'		=> PATH_JAVASCRIPT,
			'package'	=> PATH_THIRD,
			'fp_module'	=> PATH_MOD
		);
		
		$mock_name = '';

		foreach($types as $type => $path)
		{
			$mock_name .= $this->input->get_post($type);
			$files = explode(',', $this->input->get_post($type));
			
			foreach($files as $file)
			{
				if ($type == 'package' OR $type == 'fp_module')
				{
					$file = $file.'/javascript/'.$file;
				}
				elseif ($type == 'file')
				{
					$parts = explode('/', $file);
					$file = array();
					
					foreach ($parts as $part)
					{
						if ($part != '..')
						{
							$file[] = $this->security->sanitize_filename($part);
						}
					}
								
					$file = implode('/', $file);
				}
				else
				{
					$file = $this->security->sanitize_filename($file);
				}
				
				$file = $path.$file.'.js';

				if (file_exists($file))
				{
					$contents .= file_get_contents($file)."\n\n";
				}
			}
		}

		$modified = $this->input->get_post('v');
		$this->_set_headers($mock_name, $modified);
		
		$this->output->set_header('Content-Length: '.strlen($contents));
		$this->output->set_output($contents);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Set Headers
	 *
	 * @access	private
     * @param	string 
	 * @return	string
	 */
    function _set_headers($file, $mtime = FALSE)
    {
		$this->output->out_type = 'cp_asset';
		$this->output->set_header("Content-Type: text/javascript");

		if ($this->config->item('send_headers') != 'y')
		{
			// All we need is content type - we're done
			return;
		}

		$max_age		= 5184000;
		$modified		= ($mtime !== FALSE) ? $mtime : @filemtime($file);
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
		
        $this->output->set_header("ETag: ".md5($modified.$file));

		// All times GMT
		$modified = gmdate('D, d M Y H:i:s', $modified).' GMT';
		$expires = gmdate('D, d M Y H:i:s', time() + $max_age).' GMT';

		$this->output->set_status_header(200);
		$this->output->set_header("Cache-Control: max-age={$max_age}, must-revalidate");
		$this->output->set_header('Vary: Accept-Encoding');
		$this->output->set_header('Last-Modified: '.$modified);
		$this->output->set_header('Expires: '.$expires);        
	}
}

/* End of file javascript.php */
/* Location: ./system/expressionengine/controllers/cp/javascript.php */