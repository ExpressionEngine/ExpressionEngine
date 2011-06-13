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
 * ExpressionEngine ExpressionEngine Info Accessory
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Accessories
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Expressionengine_info_acc {

	var $name			= 'ExpressionEngine Info';
	var $id				= 'expressionengine_info';
	var $version		= '1.0';
	var $description	= 'Links and Information about ExpressionEngine';
	var $sections		= array();

	/**
	 * Constructor
	 */
	function __construct()
	{
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

	/**
	 * Set Sections
	 *
	 * Set content for the accessory
	 *
	 * @access	public
	 * @return	void
	 */
	 function set_sections()
	{
		$this->EE->lang->loadfile('expressionengine_info');
		
		// localize Accessory display name
		$this->name = lang('expressionengine_info');
		
		// set the sections
		$this->sections[lang('resources')] = $this->_fetch_resources();
		$this->sections[lang('version_and_build')] = $this->_fetch_version();
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch Resources
	 *
	 * @access	public
	 * @return	string
	 */
	function _fetch_resources()
	{
		return '
		<ul>
			<li><a href="'.$this->EE->cp->masked_url('http://expressionengine.com/user_guide').'">'.lang('documentation').'</a></li>
			<li><a href="'.$this->EE->cp->masked_url('http://expressionengine.com/support/support_policy/').'">'.lang('support_resources').'</a></li>
			<li><a href="'.$this->EE->cp->masked_url('https://secure.expressionengine.com/download.php').'">'.lang('downloads').'</a></li>
		</ul>
		';
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch Version
	 *
	 * @access	public
	 * @return	string
	 */
	function _fetch_version()
	{
		$this->EE->load->helper('version_helper');
			
		$details = get_version_info();
		$download_url = $this->EE->cp->masked_url('https://secure.expressionengine.com/download.php');
		
		if ( ! $details)
		{
			return str_replace(array('%v', '%b'), array(APP_VER, APP_BUILD), lang('error_getting_version'));
		}

		end($details);
		$latest_version = current($details);
		
		if ($latest_version[0] > APP_VER)
		{
			$instruct_url = $this->EE->cp->masked_url($this->EE->config->item('doc_url').'installation/update.html');
			
			$str = '<p><strong>' . lang('version_update_available') . '</strong></p><br />';
			$str .= '<ul>';
			$str .= '<li>'.str_replace(array('%v', '%b'), array($latest_version[0], $latest_version[1]), lang('current_version')).'</li>';
			$str .= '<li>'.str_replace(array('%v', '%b'), array(APP_VER, APP_BUILD), lang('installed_version')).'</li>';
			$str .= '</ul>';			
			$str .= '<br /><p>'.NL.str_replace(array('%d', '%i'), array($download_url, $instruct_url), lang('version_update_inst')).'</p>';
			
			return $str;
		}
/*
		elseif($latest_version[1] > APP_BUILD)
		{
			$instruct_url = $this->EE->cp->masked_url($this->EE->config->item('doc_url').'installation/update_build.html');
			
			$str = '<p><strong>' . lang('build_update_available') . '</strong></p><br />';
			$str .= '<ul>';
			$str .= '<li>'.str_replace(array('%v', '%b'), array($latest_version[0], $latest_version[1]), lang('current_version')).'</li>';
			$str .= '<li>'.str_replace(array('%v', '%b'), array(APP_VER, APP_BUILD), lang('installed_version')).'</li>';
			$str .= '</ul>';			
			$str .= '<br /><p>'.NL.str_replace(array('%d', '%i'), array($download_url, $instruct_url), lang('build_update_inst')).'</p>';			

			return $str;
		}
*/
		
		return str_replace(array('%v', '%b'), array(APP_VER, APP_BUILD), lang('running_current'));
	}
	
}
// END CLASS

/* End of file acc.expressionengine_info.php */
/* Location: ./system/expressionengine/accessories/acc.expressionengine_info.php */