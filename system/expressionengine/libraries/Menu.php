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
 * ExpressionEngine Menu Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class EE_Menu {

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function __construct()
	{
		$this->EE =& get_instance();
		$this->EE->load->library('api');
	}	

	// --------------------------------------------------------------------

	/**
	 * Generate Menu
	 *
	 * Builds the CP menu
	 *
	 * @access	public
	 * @return	void
	 */	 
	function generate_menu($permissions = '')
	{
		if ( ! $this->EE->cp->allowed_group('can_access_cp'))
		{
			return;
		}
		
		$menu = array();
		$menu['content'] = array(
			'publish'	=> BASE.AMP.'C=content_publish',
			'edit'		=> BASE.AMP.'C=content_edit',
			'files'		=> array(
				'file_manager'					=> BASE.AMP.'C=content_files',
				'----',
				'file_upload_preferences'		=> BASE.AMP.'C=content_files'.AMP.'M=file_upload_preferences',
				'file_watermark_preferences'	=> BASE.AMP.'C=content_files'.AMP.'M=watermark_preferences',
			)
		);
		
		// 
		
		$menu['design'] = array(
			'templates'		=> array(
				'edit_templates'				=> array(),
				'template_manager'				=> BASE.AMP.'C=design'.AMP.'M=manager',
				'sync_templates'				=> BASE.AMP.'C=design'.AMP.'M=sync_templates',
				'----',
				'snippets'						=> BASE.AMP.'C=design'.AMP.'M=snippets',
				'global_variables'				=> BASE.AMP.'C=design'.AMP.'M=global_variables',
				'----',                 
				'template_preferences'			=> BASE.AMP.'C=design'.AMP.'M=template_preferences_manager',
				'global_preferences'			=> BASE.AMP.'C=design'.AMP.'M=global_template_preferences'
			),
			'message_pages' => array(
				'email_notification'			=> BASE.AMP.'C=design'.AMP.'M=email_notification',
				'user_message'					=> BASE.AMP.'C=design'.AMP.'M=user_message',
				'offline_template'				=> BASE.AMP.'C=design'.AMP.'M=system_offline'
			),
		);
		
		$menu['addons'] = array(
			'modules'				   			=> BASE.AMP.'C=addons_modules',
			'accessories'			   			=> BASE.AMP.'C=addons_accessories',
			'extensions'			   			=> BASE.AMP.'C=addons_extensions',
			'fieldtypes'			   			=> BASE.AMP.'C=addons_fieldtypes',
			'plugins'				   			=> BASE.AMP.'C=addons_plugins'
		);

		$menu['members'] = array(      
			'view_all_members'		   			=> BASE.AMP.'C=members'.AMP.'M=view_all_members',
			'member_groups'			   			=> BASE.AMP.'C=members'.AMP.'M=member_group_manager',
			'----',
			'ip_search'			   				=> BASE.AMP.'C=members'.AMP.'M=ip_search',
			'----',
			'register_member'		   			=> BASE.AMP.'C=members'.AMP.'M=new_member_form',
			'user_banning'			   			=> BASE.AMP.'C=members'.AMP.'M=member_banning',
			'activate_pending_members' 			=> BASE.AMP.'C=members'.AMP.'M=member_validation',
			'----',
			'custom_member_fields'	   			=> BASE.AMP.'C=members'.AMP.'M=custom_profile_fields',
			'member_config'   					=> BASE.AMP.'C=members'.AMP.'M=member_config'
		);

		$menu['admin'] = array(
			'channel_management' => array(
				'channels'						=> BASE.AMP.'C=admin_content'.AMP.'M=channel_management',
				'field_group_management'			=> BASE.AMP.'C=admin_content'.AMP.'M=field_group_management',
				'status_group_management'		=> BASE.AMP.'C=admin_content'.AMP.'M=status_group_management',
				'category_management'			=> BASE.AMP.'C=admin_content'.AMP.'M=category_management',
				'----',
				'global_channel_preferences'	=> BASE.AMP.'C=admin_content'.AMP.'M=global_channel_preferences'
			),
			'----',
			'general_configuration'			=> BASE.AMP.'C=admin_system'.AMP.'M=general_configuration',
			'localization_settings'			=> BASE.AMP.'C=admin_system'.AMP.'M=localization_settings',
			'email_configuration'			=> BASE.AMP.'C=admin_system'.AMP.'M=email_configuration',
			'----',
			'admin_content'	=> array(
				'default_ping_servers'			=> BASE.AMP.'C=admin_content'.AMP.'M=default_ping_servers',
				'default_html_buttons'			=> BASE.AMP.'C=admin_content'.AMP.'M=default_html_buttons'
			),
			'admin_system'	=> array(
				'database_settings'				=> BASE.AMP.'C=admin_system'.AMP.'M=database_settings',
				'output_debugging_preferences'	=> BASE.AMP.'C=admin_system'.AMP.'M=output_debugging_preferences',
				'----',
				'image_resizing_preferences'	=> BASE.AMP.'C=admin_system'.AMP.'M=image_resizing_preferences',
				'emoticon_preferences'			=> BASE.AMP.'C=admin_system'.AMP.'M=emoticon_preferences',
				'search_log_configuration'		=> BASE.AMP.'C=admin_system'.AMP.'M=search_log_configuration',
				'----',
				'config_editor'					=> BASE.AMP.'C=admin_system'.AMP.'M=config_editor',
			),
			'security_and_privacy'		=> array(
				'security_session_preferences'	=> BASE.AMP.'C=admin_system'.AMP.'M=security_session_preferences',
				'cookie_settings'				=> BASE.AMP.'C=admin_system'.AMP.'M=cookie_settings',
				'----',
				'word_censoring'				=> BASE.AMP.'C=admin_system'.AMP.'M=word_censoring',
				'tracking_preferences'			=> BASE.AMP.'C=admin_system'.AMP.'M=tracking_preferences',
				'captcha_preferences'			=> BASE.AMP.'C=admin_system'.AMP.'M=captcha_preferences',
				'throttling_configuration'		=> BASE.AMP.'C=admin_system'.AMP.'M=throttling_configuration'
			)
		);
		
		
		$menu['tools'] = array(
			'tools_communicate'					=> BASE.AMP.'C=tools_communicate',
			'----',
			'tools_utilities'	=> array(
				'translation_tool'				=> BASE.AMP.'C=tools_utilities'.AMP.'M=translation_tool',
				'import_utilities'				=> BASE.AMP.'C=tools_utilities'.AMP.'M=import_utilities',
				'php_info'						=> BASE.AMP.'C=tools_utilities'.AMP.'M=php_info'
			),
			'tools_data'		=> array(
				'sql_manager'					=> BASE.AMP.'C=tools_data'.AMP.'M=sql_manager',
				'clear_caching'					=> BASE.AMP.'C=tools_data'.AMP.'M=clear_caching',
				'search_and_replace'			=> BASE.AMP.'C=tools_data'.AMP.'M=search_and_replace',
				'recount_stats'					=> BASE.AMP.'C=tools_data'.AMP.'M=recount_stats'
			),
			'tools_logs'		=> array(
				'view_cp_log'					=> BASE.AMP.'C=tools_logs'.AMP.'M=view_cp_log',
				'view_search_log'				=> BASE.AMP.'C=tools_logs'.AMP.'M=view_search_log',
				'view_throttle_log'				=> BASE.AMP.'C=tools_logs'.AMP.'M=view_throttle_log',
				'view_email_log'				=> BASE.AMP.'C=tools_logs'.AMP.'M=view_email_log'
			)
		);
		
		
		// Add channels

		$this->EE->api->instantiate('channel_structure');
		$channels = $this->EE->api_channel_structure->get_channels();

		if ($channels != FALSE AND $channels->num_rows() > 0)
		{
			$menu['content']['publish'] = array();
			
			foreach($channels->result() as $channel)
			{
				$menu['content']['publish'][$channel->channel_title] = BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.$channel->channel_id;
			}
			
			if (count($menu['content']['publish']) == 1)
			{
				$menu['content']['publish'] = current($menu['content']['publish']);
			}
		}
		
		// Add Templates and Themes
		
		$this->EE->load->model('template_model');

		// Grab all the groups a user is assigned to
		$allowed_groups = $this->EE->session->userdata('assigned_template_groups');

		// Grab all of the template groups in their desired order
		$template_groups = $this->EE->template_model->get_template_groups();
		$template_groups = $template_groups->result_array();
		
		// If there are allowed groups or the user is a Super Admin, go through with it
		if (count($allowed_groups) OR $this->EE->session->userdata('group_id') == 1)
		{
			// In the event $allowed_groups has information in it, build a where clause for them
			$additional_where = count($allowed_groups) ? array('template_groups.group_id' => array_keys($allowed_groups)) : array();
			
			$templates = $this->EE->template_model->get_templates(NULL, array('template_groups.group_id'), $additional_where);
			
			if ($templates->num_rows() > 0)
			{
				$by_group = array();
				
				// Reorganize the results so they're sorted by group name
				foreach($templates->result() as $row)
				{
					$by_group[$row->group_name][] = $row;
				}
				
				// Using the template groups as a guide for ordering, build the list of templates
				foreach($template_groups as $group)
				{
					$group_id   = $group['group_id'];
					$group_name = $group['group_name'];
					
					if ( ! isset($by_group[$group_name]))
					{
						continue;
					}
					
					$templates  = $by_group[$group_name];
					
					foreach($templates as $row)
					{
						$menu['design']['templates']['edit_templates'][$group_name][$row->template_name] = BASE.AMP.'C=design'.AMP.'M=edit_template'.AMP.'id='.$row->template_id;
					}

					// All groups have an index template, so row->group_id will always be set :)
					$menu['design']['templates']['edit_templates'][$group_name][lang('nav_edit_template_group_more')] = BASE.AMP.'C=design'.AMP.'M=manager'.AMP.'tgpref='.$group_id;
					$menu['design']['templates']['edit_templates'][$group_name][] = '----';
					$menu['design']['templates']['edit_templates'][$group_name][lang('nav_edit_template_group')] = BASE.AMP.'C=design'.AMP.'M=manager'.AMP.'tgpref='.$group_id;
					$menu['design']['templates']['edit_templates'][$group_name][lang('nav_create_template')] = BASE.AMP.'C=design'.AMP.'M=new_template'.AMP.'group_id='.$group_id;
				}
				
				unset($by_group);
				$menu['design']['templates']['edit_templates'][] = '----';
			}

			$menu['design']['templates']['edit_templates'][lang('nav_create_group')] = BASE.AMP.'C=design'.AMP.'M=new_template_group';
		}
		else
		{
			unset($menu['design']['edit_templates']);
		}

		if ($this->EE->db->table_exists('forums'))
		{
			$menu['design']['themes']['forum_themes'] = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=forum'.AMP.'method=forum_templates';
		}
		
		if ($this->EE->db->table_exists('wikis'))
		{
			$menu['design']['themes']['wiki_themes'] = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=wiki'.AMP.'method=list_themes';
		}
		
		if ( ! IS_FREELANCER)
		{
			$menu['design']['themes']['member_profile_templates'] = BASE.AMP."C=design".AMP."M=member_profile_templates";
		}

		$menu = $this->_remove_blocked_menu_items($menu);
		$menu = $this->_add_overviews($menu);
	
 		/* -------------------------------------------
		/* 'cp_menu_array' hook.
		/*  - Modify menu array
		/*  - Added: 2.1.5
		*/
			if ($this->EE->extensions->active_hook('cp_menu_array') === TRUE)
			{
				$menu = $this->EE->extensions->call('cp_menu_array', $menu);
			}
		/*
		/* -------------------------------------------*/
		

		// Only get the views once
		$this->menu_parent	= $this->EE->load->view('_shared/menu/item_parent', '', TRUE);
		$this->menu_item	= $this->EE->load->view('_shared/menu/item', '', TRUE);
		$this->menu_divider	= $this->EE->load->view('_shared/menu/item_divider', '', TRUE);

		// Main menu, custom tabs, help link - in that order
		$menu_string  = $this->_process_menu($menu);
		$menu_string .= $this->_process_menu($this->_fetch_quick_tabs(), 0, FALSE);
		$menu_string .= $this->_process_menu(array('help' => $this->generate_help_link()), 0, TRUE, '', 'external');
		
		// Visit Site / MSM Switcher gets an extra class
		$menu_string .= $this->_process_menu($this->_fetch_site_list(), 0, FALSE, 'msm_sites');

		$this->EE->load->vars('menu_string', $menu_string);
		
		return $menu;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Generate Menu
	 *
	 * Recursively builds a menu using the above views
	 *
	 * @access	private
	 * @param	array	menu definition
	 * @return	array
	 */
	function _process_menu($m, $depth = 0, $use_lang_keys = TRUE, $li_class = '', $rel = '')
	{
		$menu = '';

		foreach($m as $name => $data)
		{
			$label = ($use_lang_keys) ? lang('nav_'.$name) : $name;
			$sub_use_lang = ( ! $use_lang_keys OR in_array($name, array('publish', 'edit_templates'))) ? FALSE : TRUE;
			$link_class = $depth ? '' : 'first_level';
			
			if (is_array($data))
			{
				$menu .= str_replace(
					array(
						'{title}',
						'{link_class}',
						'{li_class}',
						'{subnav}',
						'{ul_class}',
						'{rel}'
					),
					array(
						$label,
						$link_class,
						$li_class,
						$this->_process_menu($data, $depth + 1, $sub_use_lang, $li_class, $rel),
						'',
						($rel == '') ? '' : ' rel="'.$rel.'"',
					),
					$this->menu_parent
				);
			}
			else
			{
				if ($data == '----')
				{
					$menu .= $this->menu_divider;
				}
				else
				{
					$menu .= str_replace(
						array(
							'{title}',
							'{link_class}',
							'{li_class}',
							'{url}',
							'{ul_class}',
							'{rel}'
						),
						array(
							$label,
							$link_class,
							$li_class,
							$data,
							'',
							($rel == '') ? '' : ' rel="'.$rel.'"',
						),
						$this->menu_item
					);
				}
			}
		}

		return $menu;
	}

	// --------------------------------------------------------------------

	/**
	 * Add Overview
	 *
	 * Adds the Overview links to the menu
	 *
	 * @access	public
	 * @param	array
	 * @return	array
	 */
	function _add_overviews($menu)
	{
		foreach($menu as $key => $val)
		{
			if (is_array($menu[$key]))
			{
				$menu[$key][] = '----';
				$menu[$key]['overview'] = BASE.AMP."C={$key}";
							
				if ($key == 'admin')
				{
					$menu[$key]['overview'] .= "_system";
				}
			}
		}
		
		return $menu;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Remove Blocked Menu Items
	 *
	 * Removes items from the menu that the user is not able to access.
	 *
	 * @access	private
	 * @param	array		The user's menu
	 * @return	array
	 */
	function _remove_blocked_menu_items($menu)
	{
		if ($this->EE->session->userdata('group_id') == 1)
		{
			return $menu;
		}

		if ( ! $this->EE->cp->allowed_group('can_access_content'))
		{
			unset($menu['content']);
		}
		else
		{
			if ( ! $this->EE->cp->allowed_group('can_access_publish'))
			{
				unset($menu['content']['publish']);
			}

			if ( ! $this->EE->cp->allowed_group('can_access_edit'))
			{
				unset($menu['content']['edit']);
			}
			
			if ( ! $this->EE->cp->allowed_group('can_access_files'))
			{
				unset($menu['content']['files']);
			}
			else
			{
				if ( ! $this->EE->cp->allowed_group('can_admin_upload_prefs'))
				{
					unset($menu['content']['files'][0]);
					unset($menu['content']['files']['file_upload_preferences']);
					unset($menu['content']['files']['file_watermark_preferences']);
				}				
			}
		}

		if ( ! $this->EE->cp->allowed_group('can_access_design'))
		{
			unset($menu['design']);
		}
		else // Unset module themes they do not have access to
		{
			if ( ! $this->EE->cp->allowed_group('can_admin_modules'))
			{
				unset($menu['design']['themes']['forum_themes']);
				unset($menu['design']['themes']['wiki_themes']);
			}
			elseif ($this->EE->session->userdata('group_id') != 1)
			{
				$allowed_modules = array_keys($this->EE->session->userdata('assigned_modules'));
				
				if (count($allowed_modules) == 0)
				{
					unset($menu['design']['themes']['forum_themes']);
					unset($menu['design']['themes']['wiki_themes']);					
				}
				else
				{
					$m_names = array();
					$this->EE->db->select('module_name');
					$this->EE->db->where_in('module_id', $allowed_modules);
					$query = $this->EE->db->get('modules');
					
					
					foreach ($query->result_array() as $row)
					{
						$m_names[] = $row['module_name'].'_themes';
					}
					
					if ( ! in_array('forum_themes', $m_names))
					{
						unset($menu['design']['themes']['forum_themes']);
					}

					if ( ! in_array('wiki_themes', $m_names))
					{
						unset($menu['design']['themes']['wiki_themes']);
					}					
				}
			}
			
			if ( ! $this->EE->cp->allowed_group('can_admin_mbr_templates'))
			{
				unset($menu['design']['themes']['member_profile_templates']);

				if (count($menu['design']['themes']) == 0)
				{
					unset($menu['design']['themes']);
				}
			}
			
			if ( ! $this->EE->cp->allowed_group('can_admin_design'))
			{
				unset($menu['design']['message_pages']);
			}			

			if ( ! $this->EE->cp->allowed_group('can_admin_templates'))
			{
				unset($menu['design']['templates']['edit_templates'][lang('nav_create_template')]);
				unset($menu['design']['templates']['edit_templates'][0]);
				unset($menu['design']['templates']['create_template']);
				unset($menu['design']['templates']['snippets']);
				unset($menu['design']['templates']['sync_templates']);
				unset($menu['design']['templates']['template_preferences']);				
				unset($menu['design']['templates']['global_variables']);
				unset($menu['design']['templates']['global_preferences']);
				unset($menu['design']['templates'][0]);	
			}
		}

		if ( ! $this->EE->cp->allowed_group('can_access_addons'))
		{
			unset($menu['addons']);
		}
		else
		{
			if ( ! $this->EE->cp->allowed_group('can_access_modules'))
			{
				unset($menu['addons']['modules']);
			}
		
			if ( ! $this->EE->cp->allowed_group('can_access_accessories'))
			{
				unset($menu['addons']['accessories']);
			}
		
			if ( ! $this->EE->cp->allowed_group('can_access_extensions'))
			{
				unset($menu['addons']['extensions']);
			}
		
			if ( ! $this->EE->cp->allowed_group('can_access_plugins'))
			{
				unset($menu['addons']['plugins']);
			}
			
			if ( ! $this->EE->cp->allowed_group('can_access_fieldtypes'))
			{
				unset($menu['addons']['fieldtypes']);
			}
		}
		
		if ( ! $this->EE->cp->allowed_group('can_access_members'))
		{
			unset($menu['members']);
		}
		else
		{
			$member_divider_3 = TRUE;
			$unset_count = 0;
			
			if ( ! $this->EE->cp->allowed_group('can_admin_members'))
			{
				unset($menu['members']['ip_search']);
				unset($menu['members']['register_member']);
				unset($menu['members']['activate_pending_members']);
				unset($menu['members']['custom_member_fields']);
				unset($menu['members']['member_config']);
				unset($menu['members'][1]);
				unset($menu['members'][3]);
				$unset_count++;
								
				$member_divider_3 = FALSE;								
			}

			if ( ! $this->EE->cp->allowed_group('can_ban_users'))
			{
				unset($menu['members']['user_banning']);
				$unset_count++;

				if ($member_divider_3 == FALSE)
				{
					unset($menu['members'][2]);
				}
			}

			if ( ! $this->EE->cp->allowed_group('can_admin_mbr_groups'))
			{
				unset($menu['members']['member_groups']);
				$unset_count++;
			}			
			
			if ($unset_count == 3)
			{
				unset($menu['members'][0]);
			}
		}

		if ( ! $this->EE->cp->allowed_group('can_access_admin'))
		{
			unset($menu['admin']);
		}
		else
		{
			if ( ! $this->EE->cp->allowed_group('can_access_sys_prefs'))
			{
				unset($menu['admin']['general_configuration']);
				unset($menu['admin']['localization_settings']);
				unset($menu['admin']['email_configuration']);
				unset($menu['admin']['admin_system']);
				unset($menu['admin']['security_and_privacy']);
			
				unset($menu['admin'][1]);	
			}
		
			if ( ! $this->EE->cp->allowed_group('can_access_content_prefs'))
			{
				unset($menu['admin']['channel_management']);
				unset($menu['admin']['admin_content']);
				unset($menu['admin'][0]);	
			}
			else
			{
				if ( ! $this->EE->cp->allowed_group('can_admin_channels'))
				{
					unset($menu['admin']['channel_management']);
					unset($menu['admin']['admin_content']);
					unset($menu['admin'][0]);	
				}				
			}
		}

		if ( ! $this->EE->cp->allowed_group('can_access_tools'))
		{
			unset($menu['tools']);
		}
		else
		{
			$tools_divider = FALSE;
			
			if ( ! $this->EE->cp->allowed_group('can_access_comm'))
			{
				unset($menu['tools']['tools_communicate']);	
				unset($menu['tools'][0]);							
			}			

			if ( ! $this->EE->cp->allowed_group('can_access_data'))
			{
				unset($menu['tools']['tools_data']);
			}
			else
			{
				$tools_divider = TRUE;
			}
			
			if ( ! $this->EE->cp->allowed_group('can_access_utilities'))
			{
				unset($menu['tools']['tools_utilities']);
			}
			else
			{
				$tools_divider = TRUE;
			}
			
			if ( ! $this->EE->cp->allowed_group('can_access_logs'))
			{
				unset($menu['tools']['tools_logs']);
			}
			else
			{
				$tools_divider = TRUE;
			}
			
			if ( ! $tools_divider)
			{
				unset($menu['tools'][0]);							
			}
		}

		return $menu;
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

		if (isset($this->EE->session->userdata['quick_tabs']) && $this->EE->session->userdata['quick_tabs'] != '')
		{
			foreach (explode("\n", $this->EE->session->userdata['quick_tabs']) as $row)
			{
				$x = explode('|', $row);

				$title = (isset($x['0'])) ? $x['0'] : '';
				$link  = (isset($x['1'])) ? $x['1'] : '';

				$tabs[$title] = $link;
			}
		}

		return $tabs;
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
	function _fetch_site_list()
	{
		// Add MSM Site Switcher
		$this->EE->load->model('site_model');
		
		$site_list = $this->EE->session->userdata('assigned_sites'); 
		$site_list = ($this->EE->config->item('multiple_sites_enabled') === 'y' && ! IS_FREELANCER) ? $site_list : FALSE;

		$menu = array();

		if ($site_list)
		{
			$site_backlink = $this->EE->cp->get_safe_refresh();

			if ($site_backlink)
			{
				$site_backlink = implode('|', explode(AMP, $site_backlink));
				$site_backlink = AMP."page=".strtr(base64_encode($site_backlink), '+=', '-_');
			}
			
			$menu[$this->EE->config->item('site_name')][lang('view_site')] = $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'URL='.$this->EE->functions->fetch_site_index();
			
			if ($this->EE->cp->allowed_group('can_admin_sites'))
			{
				$menu[$this->EE->config->item('site_name')][lang('edit_sites')] = BASE.AMP.'C=sites'.AMP.'M=manage_sites';
			}
			
			$menu[$this->EE->config->item('site_name')][] = '----';
			
			foreach($site_list as $site_id => $site_name)
			{
				$menu[$this->EE->config->item('site_name')][$site_name] = BASE.AMP.'C=sites'.AMP.'site_id='.$site_id.$site_backlink;
			}
		}
		else
		{
			$menu[$this->EE->config->item('site_name')] = $this->EE->config->item('base_url').$this->EE->config->item('index_page').'?URL='.$this->EE->config->item('base_url').$this->EE->config->item('index_page');
		}
		
		return $menu;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Generate Help Link
	 *
	 * Maps the current page request to a suitable location in the user guide
	 *
	 * @access	public
	 * @return	string
	 */
	function generate_help_link($class = '', $method = '', $override = FALSE, $module = FALSE)
	{
		if ($class == '')
		{
			$class = $this->EE->router->class;
		}

		if ($method == '')
		{
			$method = $this->EE->router->method;
		}

		$help_map = array(
			'addons_accessories'	=> 'cp/add-ons/accessory_manager.html',
			'addons_extensions'		=> 'cp/add-ons/extension_manager.html',
			'addons_modules'		=> 'cp/add-ons/module_manager.html',
			'addons_plugins'		=> 'cp/add-ons/plugin_manager.html',

			'addons'				=> array(
				'index'								=> 'cp/add-ons/index.html',
				'modules'							=> 'cp/add-ons/module_manager.html',
				'accessories'						=> 'cp/add-ons/accessory_manager.html',
				'extensions'						=> 'cp/add-ons/extension_manager.html',
				'fieldtypes'						=> 'cp/add-ons/fieldtype_manager.html',
				'plugins'							=> 'cp/add-ons/plugin_manager.html'
			),
						
			'admin_content'			=> array(
				'index'								=> 'cp/admin/channels/index.html',
				'category_edit'						=> 'cp/admin/channels/category_create.html',
				'edit_category_group'				=> 'cp/admin/channels/category_create_group.html',
				'category_editor'					=> 'cp/admin/channels/category_edit.html',
				'category_custom_field_group_manager'	=> 'cp/admin/channels/category_field_management.html',
				'category_management'				=>	'cp/admin/channels/category_management.html',
				'channel_add'						=> 'cp/admin/channels/channel_create.html',
				'channel_delete_confirm'			=> 'cp/admin/channels/channel_delete.html',
				'channel_edit'						=> 'cp/admin/channels/channel_edit_preferences.html',
				'channel_edit_group_assignments'	=> 'cp/admin/channels/channel_groups.html',
				'channel_management'				=> 'cp/admin/channels/channel_management.html',
				'channels'							=> 'cp/admin/channels/channel_management.html',
				'edit_custom_category_field'		=> 'cp/admin/channels/category_field_edit.html',
				'field_management'					=> 'cp/admin/channels/custom_channel_fields.html',
				'field_group_management'			=> 'cp/admin/channels/custom_channel_fields.html',
				'status_group_management'			=> 'cp/admin/channels/statuses.html',
				'field_edit'						=> 'cp/admin/channels/custom_fields_edit.html',
				'field_group_edit'					=> 'cp/admin/channels/custom_channel_fields.html',
				'default_html_buttons'				=> 'cp/admin/default_html_buttons.html',
				'default_ping_servers'				=> 'cp/admin/default_ping_servers.html',
				'global_channel_preferences'		=> 'cp/admin/channels/global_channel_preferences.html',
				'status_group_edit'					=> 'cp/admin/channels/statuses_edit.html',
				'file_upload_preferences'			=> 'cp/admin/channels/file_upload_preferences.html',
				'edit_upload_preferences'			=> 'cp/admin/channels/file_upload_preferences.html'
			),
			
			'admin_system'			=> array(
				'index'							=> 'cp/admin/index.html',								
				'captcha_preferences'			=> 'cp/admin/captcha_preferences.html',
				'database_settings'				=> 'cp/admin/database_settings.html',
				'cookie_settings'				=> 'cp/admin/cookie_settings.html',
				'email_configuration'			=> 'cp/admin/email_configuration.html',
				'emoticon_preferences'			=> 'cp/admin/emoticon_preferences.html',
				'general_configuration'			=> 'cp/admin/general_configuration.html',
				'image_resizing_preferences'	=> 'cp/admin/image_resizing_preferences.html',
				'localization_settings'			=> 'cp/admin/localization_settings.html',
				'output_debugging_preferences'	=> 'cp/admin/output_and_debugging_preferences.html',
				'search_log_configuration'		=> 'cp/admin/search_log_preferences.html',
				'config_editor'					=> 'cp/admin/config_editor.html',
				'security_session_preferences'	=> 'cp/admin/security_settings.html',
				'throttling_configuration'		=> 'cp/admin/throttling_settings.html',
				'tracking_preferences'			=> 'cp/admin/tracking_preferences.html',
				'word_censoring'				=> 'cp/admin/word_censoring.html'
			),
			
			'admin'					=> 'cp/admin/index.html',
			
			'content_edit'			=> array(
				'index'					=> 'cp/content/edit.html',
				'content_edit'			=> 'cp/content/edit.html',
				'view_comments'			=> 'cp/content/comments.html'
			),
			
			'content_publish'		=> 'cp/content/publish.html',
			
			'content_files'			=> 'cp/content/publish.html',
			
			'content'				=> array(
				'index'							=> 'cp/content/publish.html', // This is for the different channels that don't have a defined index
				'content'						=> 'cp/content/publish.html',
				'edit'							=> 'cp/content/edit.html',
				'file_manager'					=> 'cp/content/files/file_manager.html',
				'file_upload_preferences'		=> 'cp/content/files/file_upload_preferences.html',
				'file_watermark_preferences'	=> 'cp/content/files/watermark_preferences.html'
				
			),
			
			'css'					=> '',
			
			'design'				=> array(
				'index'							=> 'cp/design/templates/index.html',
				'edit_template'					=> 'cp/design/templates/edit_template.html',
				'template_preferences'			=> 'cp/design/templates/template_preferences_manager.html',
				'template_preferences_manager'	=> 'cp/design/templates/template_preferences_manager.html',
				'global_template_preferences'	=> 'cp/design/templates/global_template_preferences.html',
				'global_preferences'			=> 'cp/design/templates/global_template_preferences.html',
				'global_variables'				=> 'cp/design/templates/global_variables.html',
				'manager'						=> 'cp/design/templates/index.html',
				'new_template_group'			=> 'cp/design/templates/new_template_group.html',
				'new_template'					=> 'cp/design/templates/new_template.html',
				'manager'						=> 'cp/design/templates/templates.html',
				'template_manager'				=> 'cp/design/templates/templates.html',
				'snippets'						=> 'cp/design/templates/snippets.html',
				'sync_templates'				=> 'cp/design/templates/synchronize_templates.html',
				
				'email_notification'			=> 'cp/design/message_pages/index.html',
				'user_message'					=> 'cp/design/message_pages/index.html',
				'system_offline'				=> 'cp/design/message_pages/index.html',
				'offline_template'				=> 'cp/design/message_pages/index.html',
				
				'member_profile_templates'		=> 'cp/design/themes/member_profile_templates.html',
				'list_profile_templates'		=> 'cp/design/themes/member_profile_templates.html',
				'edit_profile_template'			=> 'cp/design/themes/member_profile_templates.html',

				'forum_themes'					=> 'modules/forum/forum_themes.html',
				'wiki_themes'					=> 'modules/wiki/wiki_templates.html',
			),
			
			'help'					=> '',
			
			'homepage'				=> 'cp/index.html',
			
			'javascript'			=> '',
			
			'login'					=> 'cp/',
			
			'members'				=> array(
				'index'					=> 'cp/members/index.html',
				'new_member_form'		=> 'cp/members/new_member_registration.html',
				'register_member'		=> 'cp/members/new_member_registration.html',
				'member_banning'		=> 'cp/members/user_banning.html',
				'user_banning'			=> 'cp/members/user_banning.html',
				'member_validation'		=> 'cp/members/activate_pending_members.html',
				'activate_pending_members'	=> 'cp/members/activate_pending_members.html',
				'member_group_manager'	=> 'cp/members/member_groups.html',
				'member_groups'			=> 'cp/members/member_groups.html',
				'edit_member_group'		=> 'cp/members/member_groups_edit.html',
				'custom_profile_fields'	=> 'cp/members/custom_member_fields.html',
				'custom_member_fields'	=> 'cp/members/custom_member_fields.html',
				'edit_profile_field'	=> 'cp/members/custom_profile_fields_edit.html',
				'member_config'			=> 'cp/members/membership_preferences.html',
				'view_all_members'		=> 'cp/members/view_members.html',
				'ip_search'				=> 'cp/members/ip_search.html'
			),
			
			'myaccount'				=> 'cp/my_account/index.html',
						
			'content_files'			=> array(
				'index'							=> 'cp/content/files/file_manager.html',
				'file_manager'					=> 'cp/content/files/file_manager.html',
				'edit_upload_preferences'		=> 'cp/content/files/file_upload_preferences.html',
				'file_upload_preferences'		=> 'cp/content/files/file_upload_preferences.html',					
				'watermark_preferences'			=> 'cp/content/files/watermark_preferences.html'
			),
			
			'tools'					=> 'cp/tools/index.html',
			'tools_communicate'		=> 'cp/tools/communicate.html',

			'tools_utilities'		=> array(
				'config_editor'				=> 'cp/tools/utilities/config_editor.html',
				'import_utilities'			=> 'cp/tools/utilities/import_utilities.html',
				'php_info'					=> 'cp/tools/utilities/php_info.html',
				'translation_tool'			=> 'cp/tools/utilities/translation_utility.html',
				'member_import'				=> 'cp/tools/utilities/member_import/index.html',
				'pair_fields'				=> 'cp/tools/utilities/member_import/index.html',
				'convert_from_delimited'	=> 'cp/tools/utilities/member_import/convert_to_xml.html',
				'import_from_xml'			=> 'cp/tools/utilities/member_import/import_from_xml.html',
				'confirm_xml_form'			=> 'cp/tools/utilities/member_import/import_from_xml.html'
			),
			
			'tools_data'			=> array(
				'index'					=> 'cp/tools/index.html',
				'clear_caching'			=> 'cp/tools/data/clear_cached_data_files.html',
				'recount_stats'			=> 'cp/tools/data/recount_statistics.html',
				'search_and_replace'	=> 'cp/tools/data/search_and_replace.html',
				'sql_view_database'		=> 'cp/tools/data/sql_manage_tables.html',
				'sql_run_query'			=> 'cp/tools/data/sql_manage_tables.html',
				'sql_manager'			=> 'cp/tools/data/sql_manager.html',
				'sql_processlist'		=> 'cp/tools/data/sql_process_list.html',
				'sql_query_form'		=> 'cp/tools/data/sql_query_form.html',
				'sql_status'			=> 'cp/tools/data/sql_status_info.html',
				'sql_system_vars'		=> 'cp/tools/data/sql_system_variables.html'
			),

			'tools_logs'			=> array(
				'index'				=> 'cp/tools/index.html',
				'view_cp_log'		=> 'cp/tools/logs/cp_log.html',
				'view_email_log'	=> 'cp/tools/logs/email_console_logs.html',
				'view_search_log'	=> 'cp/tools/logs/search_log.html',
				'view_throttle_log'	=> 'cp/tools/logs/throttle_log.html',
			),

			// Consider new doc pages specifically for the cp links
			'sites'					=> array(
				'index'					=> 'cp/sites/index.html',
				'add_edit_site'			=> 'cp/sites/createsite.html'
			)
		);
		
		$page = $this->EE->config->item('doc_url');
		
		if ( ! isset($help_map[$class]))
		{
			return $page;
		}

		// In some cases, multiple controllers need to be treated as one for link finding purposes
		// This usually happens on shared "landing pages"
		if ($override == 'tools')
		{
			$help_map['tools'] = array_merge(array('tools_communicate'=>$help_map['tools_communicate']), $help_map['tools_data'], $help_map['tools_logs'], $help_map['tools_utilities']);
		}

		if ($override == 'admin')
		{
			$help_map['admin'] = array_merge($help_map['admin_content'], $help_map['admin_system']);
		}

		if ($class == 'addons_modules' && ($module !== FALSE OR ($module = $this->EE->input->get('module')) !== FALSE))
		{
			// check for native / third-party, build link accordingly
			if (in_array($module, $this->EE->core->native_modules))
			{
				// gotta love matching naming schemes!
				$page .= "modules/".$module."/index.html";
			}
			else
			{
				$this->EE->load->library('security');
				
				$module = $this->EE->security->sanitize_filename($module);

				if (file_exists(PATH_THIRD.$module.'/config/help_menu.php'))
				{
					require_once PATH_THIRD.$module.'/config/help_menu.php';
					$method = ($this->EE->input->get('method') !== FALSE) ? $this->EE->input->get('method') : 'index';
					$page = (isset($help_menu[$method])) ? $help_menu[$method] : $help_map['addons_modules'];
				}
				else
				{
					$page = $help_map['addons_modules'];	
				}
			}
		}
		elseif (is_array($help_map[$class]) && isset($help_map[$class][$method]))
		{
			$page .= $help_map[$class][$method];
		}
		else
		{
			if (is_array($help_map[$class]))
			{
				$page .= $help_map[$class]['index'];
			}
			else
			{
				$page .= $help_map[$class];
			}
		}

		return $page;
	}

	// --------------------------------------------------------------------
	
}
// END CLASS

/* End of file Menu.php */
/* Location: ./system/expressionengine/libraries/Menu.php */