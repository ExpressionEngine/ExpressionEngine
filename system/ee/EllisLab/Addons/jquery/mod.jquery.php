<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
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
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Jquery {

	var $return_data = '';

	/**
	 * Constructor
	 */
	function __construct()
	{
		ee()->load->library('logger');
		ee()->logger->deprecated('3.2.0');
		if ( ! defined('PATH_JQUERY'))
		{
			define('PATH_JQUERY', PATH_THEMES.'asset/javascript/'.PATH_JS.'/jquery/');
		}

		ee()->lang->loadfile('jquery');
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
		ee()->output->enable_profiler(FALSE);

		// some options -- tag parameters have precedence over get/post
		foreach (array('file', 'plugin', 'ui', 'effect') as $param)
		{
			if (isset(ee()->TMPL) && is_object(ee()->TMPL))
			{
				${$param} = ee()->TMPL->fetch_param($param);
			}
			else
			{
				${$param} = FALSE;
			}
		}

		if ($file === FALSE)
		{
			if ($plugin !== FALSE OR ($plugin = ee()->input->get_post('plugin')) !== FALSE)
			{
				$file = PATH_JQUERY.'plugins/'.ee()->security->sanitize_filename($plugin).'.js';
			}
			elseif ($ui !== FALSE OR ($ui = ee()->input->get_post('ui')) !== FALSE)
			{
				$file = PATH_JQUERY.'ui/jquery.ui.'.ee()->security->sanitize_filename($ui).'.js';
			}
			elseif ($effect !== FALSE OR ($effect = ee()->input->get_post('effect')) !== FALSE)
			{
				$file = PATH_JQUERY.'ui/jquery.effects.'.ee()->security->sanitize_filename($effect).'.js';
			}
			elseif (($file = ee()->input->get_post('file')) !== FALSE)
			{
				$file = APPPATH.'javascript/'.ee()->security->sanitize_filename($file).'.js';
			}
			else
			{
				$file = PATH_JQUERY.'jquery.js';
			}
		}
		else
		{
			$file = APPPATH.'javascript/'.ee()->security->sanitize_filename($file).'.js';
		}

		if ( ! file_exists($file))
		{

			if (ee()->config->item('debug') >= 1)
			{
				ee()->output->fatal_error(ee()->lang->line('missing_jquery_file'));
			}
			else
			{
				return FALSE;
			}

		}

		ee()->output->send_cache_headers(filemtime($file));

		// Grab the file, content length and serve
		// it up with the proper content type!

		$contents = file_get_contents($file);

		if (ee()->config->item('send_headers') == 'y')
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
			if ((${$param} = ee()->TMPL->fetch_param($param)) !== FALSE)
			{
				return $this->return_data = ee()->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT=jquery&amp;'.$param.'='.${$param};
			}
		}

		// nothing?  Just drop a link to the main jQuery file
		return $this->return_data = ee()->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT=jquery';
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
			if ((${$param} = ee()->TMPL->fetch_param($param)) !== FALSE)
			{
				$src = ee()->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT=jquery&amp;'.$param.'='.${$param};
			}
		}

		// nothing?  Just drop a link to the main jQuery file
		$src = ($src == '') ? $this->return_data = ee()->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT=jquery' : $src;

		return $this->return_data = '<script type="text/javascript" charset="utf-8" src="'.$src.'"></script>';
	}

	// --------------------------------------------------------------------

}
// End Jquery Class

// EOF
