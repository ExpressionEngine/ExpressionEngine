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
 * ExpressionEngine Stylesheet Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class EE_Stylesheet {

	var $style_cache = array();

	/**
	 * Request CSS Template
	 *
	 * Handles CSS requests for the standard Template engine
	 *
	 * @access	public
	 * @return	void
	 */
	function request_css_template()
	{
		if (in_array(ee()->uri->segment(1), ee()->uri->reserved) && ee()->uri->segment(2) !== FALSE)
		{
			$stylesheet = ee()->uri->segment(2).'/'.ee()->uri->segment(3);
		}
		else
		{
			$stylesheet = (isset($_GET['css'])) ? $_GET['css'] : '';
		}

		if (rtrim($stylesheet, '/') == '_ee_channel_form_css')
		{
			return $this->_ee_channel_form_css();
		}

		$stylesheet = preg_replace("/\.v\.[0-9]{10}/", '', $stylesheet);  // Remove version info

		if ($stylesheet == '' OR strpos($stylesheet, '/') === FALSE)
		{
			show_404();
		}

		if ( ! isset($this->style_cache[$stylesheet]))
		{
			$ex =  explode("/", $stylesheet);

			if (count($ex) != 2)
			{
				show_404();
			}

			ee()->db->select('templates.template_data, templates.template_name,	templates.edit_date');
			ee()->db->from(array('templates', 'template_groups'));
			ee()->db->where(ee()->db->dbprefix('templates').'.group_id',
								ee()->db->dbprefix('template_groups').'.group_id', FALSE);
			ee()->db->where('templates.template_name', $ex[1]);
			ee()->db->where('template_groups.group_name', $ex[0]);
			ee()->db->where('templates.template_type', 'css');
			ee()->db->where('templates.site_id', ee()->config->item('site_id'));

			$query = ee()->db->get();

			if ($query->num_rows() == 0)
			{
				show_404();
			}

			$row = $query->row_array();

			/** -----------------------------------------
			/**  Retreive template file if necessary
			/** -----------------------------------------*/

			if (ee()->config->item('save_tmpl_files') == 'y')
			{
				ee()->load->helper('file');
				$basepath = PATH_TMPL.ee()->config->item('site_short_name').'/';
				$basepath .= $ex['0'].'.group/'.$row['template_name'].'.css';

				$str = read_file($basepath);
				$row['template_data'] = ($str !== FALSE) ? $str: $row['template_data'];
			}

			$this->style_cache[$stylesheet] = str_replace(LD.'site_url'.RD, stripslashes(ee()->config->item('site_url')), $row['template_data']);
		}

		$this->_send_css($this->style_cache[$stylesheet], $row['edit_date']);
	}

	// --------------------------------------------------------------------

	/**
	 * EE Channel:form CSS
	 *
	 * Provides basic CSS for channel:form functionality on the frontend
	 *
	 * @return	void
	 */
	private function _ee_channel_form_css()
	{
		$files[] = PATH_THEMES.'cform/css/eecms-cform.min.css';

		$out = '';

		foreach ($files as $file)
		{
			if (file_exists($file))
			{
				$out .= file_get_contents($file);
			}
		}

		$out = str_replace('../../asset/', URL_THEMES_GLOBAL_ASSET, $out);

		$this->_send_css($out, time());
	}

	// --------------------------------------------------------------------

	/**
	 * Send CSS
	 *
	 * Sends CSS with cache headers
	 *
	 * @access	public
	 * @param	string	stylesheet contents
	 * @param	int		Unix timestamp (GMT/UTC) of last modification
	 * @return	void
	 */
	function _send_css($stylesheet, $modified)
	{
		if (ee()->config->item('send_headers') == 'y')
		{
			$max_age		= 604800;
			$modified_since	= ee()->input->server('HTTP_IF_MODIFIED_SINCE');

			// Remove anything after the semicolon

			if ($pos = strrpos($modified_since, ';') !== FALSE)
			{
				$modified_since = substr($modified_since, 0, $pos);
			}

			// If the file is in the client cache, we'll
			// send a 304 and be done with it.

			if ($modified_since && (strtotime($modified_since) == $modified))
			{
				ee()->output->set_status_header(304);
				exit;
			}

			// All times GMT
			$modified = gmdate('D, d M Y H:i:s', $modified).' GMT';
			$expires = gmdate('D, d M Y H:i:s', time() + $max_age).' GMT';

			ee()->output->set_status_header(200);
			@header("Cache-Control: max-age={$max_age}, must-revalidate");
			@header('Last-Modified: '.$modified);
			@header('Expires: '.$expires);
			@header('Content-Length: '.strlen($stylesheet));
		}

		header("Content-type: text/css");
		exit($stylesheet);
	}

	// --------------------------------------------------------------------

}
// END CLASS

// EOF
