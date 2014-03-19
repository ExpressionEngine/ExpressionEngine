<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @link		http://ellislab.com
 */
class EE_Stylesheet {

	var $style_cache = array();


	function __construct()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

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
			$stylesheet = $_GET['css'];
		}

		if (rtrim($stylesheet, '/') == '_ee_channel_form_css')
		{
			return $this->_ee_channel_form_css();
		}

		$stylesheet = preg_replace("/\.v\.[0-9]{10}/", '', $stylesheet);  // Remove version info

		if ($stylesheet == '' OR strpos($stylesheet, '/') === FALSE)
		{
			exit;
		}

		if ( ! isset($this->style_cache[$stylesheet]))
		{
			$ex =  explode("/", $stylesheet);

			if (count($ex) != 2)
			{
				exit;
			}

			ee()->db->select('templates.template_data, templates.template_name,
									templates.save_template_file, templates.edit_date');
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
				exit;
			}

			$row = $query->row_array();

			/** -----------------------------------------
			/**  Retreive template file if necessary
			/** -----------------------------------------*/

			if (ee()->config->item('save_tmpl_files') == 'y' AND ee()->config->item('tmpl_file_basepath') != '' AND $row['save_template_file'] == 'y')
			{
				ee()->load->helper('file');
				$basepath = ee()->config->slash_item('tmpl_file_basepath').'/'.ee()->config->item('site_short_name').'/';
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
		$files[] = PATH_THEMES.'cp_themes/default/css/jquery-ui-1.8.16.custom.css';
		$files[] = PATH_THEMES.'cp_themes/default/css/channel_form.css';

		$out = '';

		foreach ($files as $file)
		{
			if (file_exists($file))
			{
				$out .= file_get_contents($file);

				if ($file == PATH_THEMES.'cp_themes/default/css/jquery-ui-1.8.16.custom.css')
				{
					$theme_url = ee()->config->item('theme_folder_url').'cp_themes/'.ee()->config->item('cp_theme');

					$out = str_replace('url(images/', 'url('.$theme_url.'/images/', $out);
				}
			}
		}

		$cp_theme  = ee()->config->item('cp_theme');
		$cp_theme_url = ee()->config->slash_item('theme_folder_url').'cp_themes/'.$cp_theme.'/';

		$out = str_replace('../images', $cp_theme_url.'images', $out);
		$out = str_replace('<?=$cp_theme_url?>', $cp_theme_url, $out);


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

/* End of file Stylesheet.php */
/* Location: ./system/expressionengine/libraries/Stylesheet.php */