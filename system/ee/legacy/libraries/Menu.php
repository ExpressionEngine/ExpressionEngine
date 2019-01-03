<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use  EllisLab\ExpressionEngine\Service\Sidebar\Sidebar;

/**
 * Menu
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
		$menu['custom']   = NULL;

		$custom = ee('CP/CustomMenu');

		// prep the hook data
		$which = 'cp_custom_menu';

		$byclass = array();
		$active = ee()->extensions->active_hook($which);
		$hooks = ee()->extensions->get_active_hook_info($which) ?: array();

		foreach ($hooks as $priority => $calls)
		{
			foreach ($calls as $class => $metadata)
			{
				$byclass[$class][] = $metadata;
			}
		}

		$args = array($custom);
		$items = ee('Model')->get('MenuItem')
			->fields('MenuItem.*', 'Children.*')
			->with(array('Set' => 'MemberGroups'), 'Children')
			->filter('MemberGroups.group_id', ee()->session->userdata('group_id'))
			->order('MenuItem.sort')
			->order('Children.sort')
			->all();

		foreach ($items as $item)
		{
			if ($active && $item->type == 'addon' && isset($byclass[$item->data]))
			{
				foreach ($byclass[$item->data] as $metadata)
				{
					ee()->extensions->call_class($item->data, $which, $metadata, $args);
				}
			}
			elseif ($item->type == 'submenu')
			{
				$sub = $custom->addSubmenu($item->name);

				foreach ($item->Children as $child)
				{
					$sub->addItem($child->name, $child->data);
				}
			}
			elseif ($item->parent_id == 0)
			{
				$custom->addItem($item->name, $item->data);
			}
		}

		$menu['custom'] = $custom;

		return $menu;
	}

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
		$site_list = (ee()->config->item('multiple_sites_enabled') === 'y') ? $site_list : FALSE;

		$menu = array();

		if ($site_list)
		{
			foreach($site_list as $site_id => $site_name)
			{
				if ($site_id != ee()->config->item('site_id'))
				{
					$menu[$site_name] = ee('CP/URL')->make('msm/switch_to/' . $site_id);
				}
			}
		}

		return $menu;
	}

	/**
	 * Get channels the user currently has access to for putting into the
	 * Create and Edit links in the menu
	 *
	 * @access	private
	 * @return	array	Array of channels and their edit/publish links
	 */
	private function _channels_menu()
	{
		$channels_query = ee('Model')->get('Channel')
			->fields('channel_id', 'channel_title', 'max_entries', 'total_records')
			->order('channel_title', 'ASC');

		$allowed_channels = ee()->session->userdata('assigned_channels');
		if (count($allowed_channels))
		{
			$channels = $channels_query->filter('channel_id', 'IN', array_keys($allowed_channels));
		}

		$menu['create'] = array();
		$menu['edit'] = array();

		if (isset($channels))
		{
			foreach($channels->all() as $channel)
			{
				$filtered_by_channel = ee('CP/URL')->make('publish/edit', array('filter_by_channel' => $channel->channel_id));

				// Edit link
				$menu['edit'][$channel->channel_title] = $filtered_by_channel;

				// Only add Create link if channel has room for more entries
				if (empty($channel->max_entries) OR
					($channel->max_entries != 0 && $channel->total_records < $channel->max_entries))
				{
					// Create link
					$menu['create'][$channel->channel_title] = ee('CP/URL')->make('publish/create/' . $channel->channel_id);
				}

				// If there's a limit of 1, just send them to the edit screen for that entry
				if ( ! empty($channel->max_entries) &&
					$channel->total_records == 1 && $channel->max_entries == 1)
				{
					$entry = ee('Model')->get('ChannelEntry')
						->filter('channel_id', $channel->channel_id)
						->first();

					// Just in case $channel->total_records is inaccurate
					if ($entry)
					{
						$menu['edit'][$channel->channel_title] = ee('CP/URL')->make('publish/edit/entry/' . $entry->getId());
					}
				}
			}
		}

		return $menu;
	}

	/**
	 * Fetch the develop menu
	 *
	 * @access	private
	 * @return	array
	 */
	private function _develop_menu()
	{
		$menu = array();

		if (ee()->cp->allowed_group('can_admin_channels') &&
			ee()->cp->allowed_group_any(
			'can_create_channels',
			'can_edit_channels',
			'can_delete_channels',
			'can_create_channel_fields',
			'can_edit_channel_fields',
			'can_delete_channel_fields',
			'can_create_statuses',
			'can_delete_statuses',
			'can_edit_statuses',
			'can_create_categories',
			'can_edit_categories',
			'can_delete_categories'
		))
		{
			$sections = array(
				'channels' => 'channels',
				'channel_fields' => 'fields',
				'categories' => 'categories'
			);

			foreach ($sections as $name => $path)
			{
				if (ee()->cp->allowed_group_any(
					"can_create_{$name}",
					"can_edit_{$name}",
					"can_delete_{$name}"
				))
				{
					$name = $name == 'channel_fields' ? 'fields' : $name;
					$menu[$name] = ee('CP/URL')->make($path);
				}
			}
		}

		if (ee()->cp->allowed_group('can_access_design'))
		{
			$menu['templates'] = ee('CP/URL')->make('design');
		}

		if (ee()->config->item('multiple_sites_enabled') == 'y' && ee()->cp->allowed_group('can_admin_sites'))
		{
			$menu['msm_manager'] = ee('CP/URL')->make('msm');
		}

		if (ee()->cp->allowed_group('can_access_addons'))
		{
			$menu['addons'] = ee('CP/URL')->make('addons');
		}

		if (ee()->cp->allowed_group('can_access_utilities'))
		{

			$utility_options = array(
				'can_access_comm' => ee('CP/URL')->make('utilities'),
				'can_access_translate' => ee('CP/URL')->make('utilities/translate'),
				'can_access_import' => ee('CP/URL')->make('utilities/member-import'),
				'can_access_sql_manager' => ee('CP/URL')->make('utilities/sql'),
				'can_access_data' => ee('CP/URL')->make('utilities/cache')
				);

			foreach ($utility_options as $allow => $link)
			{
				if (ee()->cp->allowed_group($allow))
				{
					$menu['utilities'] = $link;
					break;
				}
			}

			// If none of the above are allowed, see if addon admin is
			// If so, land on extension debug page

			if ( ! isset($menu['utilities']))
			{
				if (ee()->cp->allowed_group('can_access_addons')
					&& ee()->cp->allowed_group('can_admin_addons'))
				{
					$menu['utilities'] = ee('CP/URL')->make('utilities/extensions');
				}
			}
		}

		if (ee()->cp->allowed_group('can_access_logs'))
		{
			$menu['logs'] = ee('CP/URL')->make('logs');
		}

		return $menu;
	}

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

	/**
	 * Sets up left sidebar navigation given an array of data like this:
	 *
	 * array(
	 *     'key_of_heading' => ee('CP/URL')->make('optional/link'),
	 *     'heading_with_no_link',
	 *     array(
	 *         'item_in_subsection' => ee('CP/URL')->make('sub/section')
	 *     )
	 * )
	 *
	 * @param	array	$nav	Array of navigation data like above
	 * @return	void
	 */
	public function register_left_nav($nav)
	{
		if ($nav instanceof Sidebar)
		{
			ee()->view->left_nav = $nav->render();
		}
		else
		{
			ee()->view->left_nav = ee()->load->view(
				'_shared/left_nav',
				array('nav' => $nav),
				TRUE
			);
		}
	}

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

// EOF
