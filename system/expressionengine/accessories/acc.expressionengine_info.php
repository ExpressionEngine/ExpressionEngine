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
 * ExpressionEngine ExpressionEngine Info Accessory
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Accessories
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
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
		ee()->lang->loadfile('expressionengine_info');

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
			<li><a href="'.ee()->cp->masked_url('http://ellislab.com/expressionengine/user-guide/').'">'.lang('documentation').'</a></li>
			<li><a href="'.ee()->cp->masked_url('http://ellislab.com/support/').'">'.lang('support_resources').'</a></li>
			<li><a href="'.ee()->cp->masked_url('https://store.ellislab.com/manage').'">'.lang('downloads').'</a></li>
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
		ee()->load->library('el_pings');
		$details = ee()->el_pings->get_version_info();

		$download_url = ee()->cp->masked_url('https://store.ellislab.com/manage');

		if ( ! $details)
		{
			return str_replace(array('%v', '%b'), array(APP_VER, APP_BUILD), lang('error_getting_version'));
		}

		end($details);
		$latest_version = current($details);

		if ($latest_version[0] > APP_VER)
		{
			$instruct_url = ee()->cp->masked_url(ee()->config->item('doc_url').'installation/update.html');

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
			$instruct_url = ee()->cp->masked_url(ee()->config->item('doc_url').'installation/update_build.html');

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