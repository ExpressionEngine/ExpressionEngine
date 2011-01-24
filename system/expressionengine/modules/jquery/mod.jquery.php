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
 * ExpressionEngine jQuery Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Jquery {

	var $return_data = '';
	
	/**
	 * Constructor
	 */
	function Jquery()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

		if ( ! defined('PATH_JQUERY'))
		{
			if ($this->EE->config->item('use_compressed_js') == 'n')
			{
				define('PATH_JQUERY', PATH_THEMES.'javascript/src/jquery/');
			}
			else
			{
				define('PATH_JQUERY', PATH_THEMES.'javascript/compressed/jquery/');
			}
		}

		$this->EE->lang->loadfile('jquery');
	}

	// --------------------------------------------------------------------

	/**
	 * Output Javascript
	 * 
	 * Outputs Javascript files, triggered most commonly by an action request,
	 * but also available as a tag if desired.
	 *
	 * @access	public
	 * @return	string
	 */
	function output_javascript()
	{
		$this->EE->output->enable_profiler(FALSE);

		// some options -- tag parameters have precedence over get/post
		foreach (array('file', 'plugin', 'ui', 'effect') as $param)
		{
			if (isset($this->EE->TMPL) && is_object($this->EE->TMPL))
			{
				${$param} = $this->EE->TMPL->fetch_param($param);
			}
			else
			{
				${$param} = FALSE;
			}
		}

		if ($file === FALSE)
		{
			if ($plugin !== FALSE OR ($plugin = $this->EE->input->get_post('plugin')) !== FALSE)
			{
				$file = PATH_JQUERY.'plugins/'.$this->EE->security->sanitize_filename($plugin).'.js';
			}
			elseif ($ui !== FALSE OR ($ui = $this->EE->input->get_post('ui')) !== FALSE)
			{
				$file = PATH_JQUERY.'ui/jquery.ui.'.$this->EE->security->sanitize_filename($ui).'.js';
			}
			elseif ($effect !== FALSE OR ($effect = $this->EE->input->get_post('effect')) !== FALSE)
			{
				$file = PATH_JQUERY.'ui/jquery.effects.'.$this->EE->security->sanitize_filename($effect).'.js';
			}
			elseif (($file = $this->EE->input->get_post('file')) !== FALSE)
			{
				$file = APPPATH.'javascript/'.$this->EE->security->sanitize_filename($file).'.js';
			}
			else
			{
				$file = PATH_JQUERY.'jquery.js';
			}
		}
		else
		{
			$file = APPPATH.'javascript/'.$this->EE->security->sanitize_filename($file).'.js';
		}

		if ( ! file_exists($file))
		{

			if ($this->EE->config->item('debug') >= 1)
			{
				$this->EE->output->fatal_error($this->EE->lang->line('missing_jquery_file'));
			}
			else
			{
				return FALSE;
			}

		}

		$this->EE->output->send_cache_headers(filemtime($file));
		
		// Grab the file, content length and serve
		// it up with the proper content type!

		$contents = file_get_contents($file);

		if ($this->EE->config->item('send_headers') == 'y')
		{
			@header('Content-Length: '.strlen($contents));
		}

		header("Content-type: text/javascript");
		exit($contents);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Script Source
	 *
	 * Outputs an action link to a particular jQuery file
	 *
	 * @access	public
	 * @return	string
	 */
	function script_src()
	{
		foreach (array('file', 'plugin', 'ui', 'effect') as $param)
		{
			if ((${$param} = $this->EE->TMPL->fetch_param($param)) !== FALSE)
			{
				return $this->return_data = $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT=jquery&amp;'.$param.'='.${$param};
			}
		}
		
		// nothing?  Just drop a link to the main jQuery file
		return $this->return_data = $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT=jquery';
	}

	// --------------------------------------------------------------------
	
	/**
	 * Script Tag
	 *
	 * Outputs a full script tag for those who do not desire any control over it
	 *
	 * @access	public
	 * @return	string
	 */
	function script_tag()
	{
		$src = '';
		
		foreach (array('file', 'plugin', 'ui', 'effect') as $param)
		{
			if ((${$param} = $this->EE->TMPL->fetch_param($param)) !== FALSE)
			{
				$src = $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT=jquery&amp;'.$param.'='.${$param};
			}
		}
		
		// nothing?  Just drop a link to the main jQuery file
		$src = ($src == '') ? $this->return_data = $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT=jquery' : $src;
		
		return $this->return_data = '<script type="text/javascript" charset="utf-8" src="'.$src.'"></script>';
	}

	// --------------------------------------------------------------------
	
}
// End Jquery Class

/* End of file mod.jquery.php */
/* Location: ./system/expressionengine/modules/jquery/mod.jquery.php */
