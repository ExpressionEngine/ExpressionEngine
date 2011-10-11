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
 * ExpressionEngine Stylesheet Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
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
		if (in_array($this->EE->uri->segment(1), $this->EE->uri->reserved) && $this->EE->uri->segment(2) !== FALSE)
		{
			$stylesheet = $this->EE->uri->segment(2).'/'.$this->EE->uri->segment(3);
		}
		else
		{
			$stylesheet = $_GET['css'];			
		}

		if (rtrim($stylesheet, '/') == '_ee_saef_css')
		{
			return $this->_ee_saef_css();
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

			$this->EE->db->select('templates.template_data, templates.template_name, 
									templates.save_template_file, templates.edit_date');
			$this->EE->db->from(array('templates', 'template_groups'));
			$this->EE->db->where($this->EE->db->dbprefix('templates').'.group_id', 
								$this->EE->db->dbprefix('template_groups').'.group_id', FALSE);
			$this->EE->db->where('templates.template_name', $ex[1]);
			$this->EE->db->where('template_groups.group_name', $ex[0]);
			$this->EE->db->where('templates.template_type', 'css');
			$this->EE->db->where('templates.site_id', $this->EE->config->item('site_id'));
			
			$query = $this->EE->db->get();

			if ($query->num_rows() == 0)
			{
				exit;				
			}

			$row = $query->row_array();

			/** -----------------------------------------
			/**  Retreive template file if necessary
			/** -----------------------------------------*/

			if ($this->EE->config->item('save_tmpl_files') == 'y' AND $this->EE->config->item('tmpl_file_basepath') != '' AND $row['save_template_file'] == 'y')
			{
				$this->EE->load->helper('file');
				$basepath = $this->EE->config->slash_item('tmpl_file_basepath').'/'.$this->EE->config->item('site_short_name').'/';
				$basepath .= $ex['0'].'.group/'.$row['template_name'].'.css';

				$str = read_file($basepath);
				$row['template_data'] = ($str !== FALSE) ? $str: $row['template_data'];
			}

			$this->style_cache[$stylesheet] = str_replace(LD.'site_url'.RD, stripslashes($this->EE->config->item('site_url')), $row['template_data']);
		}

		$this->_send_css($this->style_cache[$stylesheet], $row['edit_date']);
	}

	// --------------------------------------------------------------------
	
	/**
	 * EE SAEF CSS
	 *
	 * Provides CSS for the SAEF file upload utility on the front end
	 *
	 * @access	private
	 * @return	void
	 */
	function _ee_saef_css()
	{
		// $files[] = PATH_THEMES.'jquery_ui/default/min_filebrowser.css';
        // $files[] = PATH_THEMES.'cp_themes/default/css/file_browser.css';
		$files[] = PATH_THEMES.'cp_themes/default/css/jquery-ui-1.8.16.custom.css';
		$files[] = PATH_THEMES.'cp_themes/default/css/saef.css';
		
		$out = '';
		
		foreach ($files as $file)
		{
			if (file_exists($file))
			{
				$out .= file_get_contents($file);
				
				if ($file == PATH_THEMES.'cp_themes/default/css/jquery-ui-1.8.16.custom.css')
				{
					$theme_url = $this->EE->config->item('theme_folder_url').'cp_themes/'.$this->EE->config->item('cp_theme');
					
					$out = str_replace('url(images/', 'url('.$theme_url.'/images/', $out);
				}
			}
		}

		$cp_theme  = $this->EE->config->item('cp_theme'); 
		$cp_theme_url = $this->EE->config->slash_item('theme_folder_url').'cp_themes/'.$cp_theme.'/';

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
		if ($this->EE->config->item('send_headers') == 'y')
		{
			$max_age		= 604800;
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