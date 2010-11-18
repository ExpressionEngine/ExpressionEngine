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

		// Can't do any of this if we're not allowed
		// to send any headers

		if ($this->EE->config->item('send_headers') == 'y')
		{
			$max_age		= 172800;
			$modified		= filemtime($file);
			$modified_since	= $this->EE->input->server('HTTP_IF_MODIFIED_SINCE');

			// Remove anything after the semicolon

			if ($pos = strrpos($modified_since, ';') !== FALSE)
			{
				$modified_since = substr($modified_since, 0, $pos);
			}

			// If the file is in the client cache, we'll
			// send a 304 and be done with it.

			if ($modified_since && (strtotime($modified_since) == $modified))
			{
				$this->EE->output->set_status_header(304);
				exit;
			}

			// All times GMT
			$modified = gmdate('D, d M Y H:i:s', $modified).' GMT';
			$expires = gmdate('D, d M Y H:i:s', time() + $max_age).' GMT';

			$this->EE->output->set_status_header(200);
			@header("Cache-Control: max-age={$max_age}, must-revalidate");
			@header('Last-Modified: '.$modified);
			@header('Expires: '.$expires);
		}

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
