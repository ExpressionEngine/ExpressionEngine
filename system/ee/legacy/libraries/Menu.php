<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Menu Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Menu {

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function __construct()
	{
		ee()->load->library('api');
	}

	// --------------------------------------------------------------------

	/**
	 * Generate Menu
	 *
	 * Puts together the links for the main menu header area
	 *
	 * @access	public
	 * @return	void
	 */
	public function generate_menu()
	{
		$menu = array();

		$menu['sites']    = $this->_site_menu();
		$menu['channels'] = $this->_channels_menu();
		$menu['develop']  = $this->_develop_menu();

		// CP-TODO: Add back cp_menu_array hook?

		return $menu;
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch Site List
	 *
	 * Returns array of sites or simply a link to the current site
	 *
	 * @access	private
	 * @return	array
	 */
	private function _site_menu()
	{
		// Add MSM Site Switcher
		ee()->load->model('site_model');

		$site_list = ee()->session->userdata('assigned_sites');
		$site_list = (ee()->config->item('multiple_sites_enabled') === 'y' && ! IS_CORE) ? $site_list : FALSE;

		$menu = array();

		if ($site_list)
		{
			$site_backlink = ee()->cp->get_safe_refresh();

			if ($site_backlink)
			{
				$site_backlink = implode('|', explode(AMP, $site_backlink));
				$site_backlink = strtr(base64_encode($site_backlink), '+=', '-_');
			}

			foreach($site_list as $site_id => $site_name)
			{
				if ($site_id != ee()->config->item('site_id'))
				{
					$menu[$site_name] = ee('CP/URL', 'sites', array('site_id' => $site_id, 'page' => $site_backlink));
				}
			}
		}

		return $menu;
	}

	// --------------------------------------------------------------------

	/**
	 * Get channels the user currently has access to for putting into the
	 * Create and Edit links in the menu
	 *
	 * @access	private
	 * @return	array	Array of channels and their edit/publish links
	 */
	private function _channels_menu()
	{
		ee()->legacy_api->instantiate('channel_structure');
		$channels = ee()->api_channel_structure->get_channels();

		$menu['create'] = array();
		$menu['edit'] = array();

		if ($channels)
		{
			foreach($channels->result() as $channel)
			{
				// Create link
				$menu['create'][$channel->channel_title] = ee('CP/URL', 'publish/create/' . $channel->channel_id);

				// Edit link
				$menu['edit'][$channel->channel_title] = ee('CP/URL', 'publish/edit', array('filter_by_channel' => $channel->channel_id));
			}
		}

		return $menu;
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch the develop menu
	 *
	 * @access	private
	 * @return	array
	 */
	private function _develop_menu()
	{
		$menu = array(
			'channel_manager'  => ee('CP/URL', 'channels'),
			'template_manager' => ee('CP/URL', 'design'),
			'addon_manager'    => ee('CP/URL', 'addons'),
			'utilities'        => ee('CP/URL', 'utilities'),
			'logs'             => ee('CP/URL', 'logs')
		);

		if ( ! ee()->cp->allowed_group('can_access_addons'))
		{
			unset($menu['addon_manager']);
		}

		if ( ! ee()->cp->allowed_group('can_access_logs'))
		{
			unset($menu['logs']);
		}

		return $menu;
	}

	// --------------------------------------------------------------------

	/**
	 * Future home of quick links
	 *
	 * @access	private
	 * @return	array
	 */
	private function _quicklinks()
	{
		$quicklinks = array();

		return $quicklinks;

		// CP-TODO: Combine quick_links and quick_tabs in updater, make this
		// method return something

		// OLD CODE FOR GETTING THESE LIINKS:

		$quicklinks = $this->member_model->get_member_quicklinks(
			ee()->session->userdata('member_id')
		);

		if (isset(ee()->session->userdata['quick_tabs']) && ee()->session->userdata['quick_tabs'] != '')
		{
			foreach (explode("\n", ee()->session->userdata['quick_tabs']) as $row)
			{
				$x = explode('|', $row);

				$title = (isset($x['0'])) ? $x['0'] : '';
				$link  = (isset($x['1'])) ? $x['1'] : '';

				// Look to see if the session is in the link; if so, it was
				// it was likely stored the old way which made for possibly
				// broken links, like if it was saved with index.php but is
				// being accessed through admin.php
				if (strstr($link, '?S=') === FALSE)
				{
					$link = BASE.AMP.$link;
				}

				$tabs[$title] = $link;
			}
		}

		return $tabs;
	}

	// --------------------------------------------------------------------

	/**
	 * Sets up left sidebar navigation given an array of data like this:
	 *
	 * array(
	 *     'key_of_heading' => ee('CP/URL', 'optional/link'),
	 *     'heading_with_no_link',
	 *     array(
	 *         'item_in_subsection' => ee('CP/URL', 'sub/section')
	 *     )
	 * )
	 *
	 * @param	array	$nav	Array of navigation data like above
	 * @return	void
	 */
	public function register_left_nav($nav)
	{
		ee()->view->left_nav = ee()->load->view(
			'_shared/left_nav',
			array('nav' => $nav),
			TRUE
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch Quick Tabs
	 *
	 * Returns an array of the user's custom nav tabs
	 *
	 * @access	private
	 * @return	array
	 */
	function _fetch_quick_tabs()
	{
		$tabs = array();

		if (isset(ee()->session->userdata['quick_tabs']) && ee()->session->userdata['quick_tabs'] != '')
		{
			foreach (explode("\n", ee()->session->userdata['quick_tabs']) as $row)
			{
				$x = explode('|', $row);

				$title = (isset($x['0'])) ? $x['0'] : '';
				$link  = (isset($x['1'])) ? $x['1'] : '';

				// Look to see if the session is in the link; if so, it was
				// it was likely stored the old way which made for possibly
				// broken links, like if it was saved with index.php but is
				// being accessed through admin.php
				if (strstr($link, '?S=') === FALSE)
				{
					$link = BASE.AMP.$link;
				}

				$tabs[$title] = $link;
			}
		}

		return $tabs;
	}

}
// END CLASS

/* End of file Menu.php */
/* Location: ./system/expressionengine/libraries/Menu.php */
