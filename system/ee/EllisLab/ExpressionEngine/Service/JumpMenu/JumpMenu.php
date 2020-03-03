<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\JumpMenu;

/**
 * Custom Menu
 */
class JumpMenu extends AbstractJumpMenu
{
	protected static $items = array(
		'1' => array(
			'home' => array(
				'icon' => 'fa-home',
				'command' => 'home',
				'command_title' => 'Go to <b>CP Home</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'homepage'
			),
			'viewEntriesIn' => array(
				'icon' => 'fa-eye',
				'command' => 'view entries in',
				'command_title' => 'View <b>Entries</b> in <i>[channel]</i>',
				'dynamic' => true,
				'addon' => false,
				'target' => 'publish/view',
				'permission'=>NULL
			),
			'createEntryIn' => array(
				'icon' => 'fa-plus',
				'command' => 'create new publish entry in',
				'command_title' => 'Create <b>Entry</b> in <i>[channel]</i>',
				'dynamic' => true,
				'addon' => false,
				'target' => 'publish/create',
				'permission'=>['can_create_entries']
			),
			'editEntry' => array(
				'icon' => 'fa-pencil-alt',
				'command' => 'edit entry titled',
				'command_title' => 'Edit <b>Entry</b> titled <i>[title]</i>',
				'dynamic' => true,
				'addon' => false,
				'target' => 'publish/edit',
				'permission'=>['can_edit_other_entries', 'can_edit_self_entries']
			),
			'createMember' => array(
				'icon' => 'fa-plus',
				'command' => 'create new publish member',
				'command_title' => 'Create <b>Member</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'members/create',
				'permission' => 'can_create_members'
			),
			'createMemberGroup' => array(
				'icon' => 'fa-plus',
				'command' => 'create new publish member group',
				'command_title' => 'Create <b>Member Group</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'members/groups/create',
				'permission' => 'can_create_roles'
			),
			'createCategoryIn' => array(
				'icon' => 'fa-plus',
				'command' => 'create new publish category in',
				'command_title' => 'Create <b>Category</b> in <i>[category group]</i>',
				'dynamic' => true,
				'addon' => false,
				'target' => 'categories/create',
				'permission' => 'can_create_categories'
			),
			'editCategory' => array(
				'icon' => 'fa-pencil-alt',
				'command' => 'edit category titled',
				'command_title' => 'Edit <b>Category</b> titled <i>[category]</i>',
				'dynamic' => true,
				'addon' => false,
				'target' => 'categories/edit',
				'permission' => 'can_edit_categories'
			),
			'viewFiles' => array(
				'icon' => 'fa-eye',
				'command' => 'view all files',
				'command_title' => 'View <b>All Files</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'files',
				'permission' => 'can_access_files'
			),
			'viewFilesIn' => array(
				'icon' => 'fa-eye',
				'command' => 'view files in',
				'command_title' => 'View <b>Files</b> in <i>[upload directory]</i>',
				'dynamic' => true,
				'addon' => false,
				'target' => 'files/view',
				'permission' => 'can_edit_files'
			),
			'editUploadDirectory' => array(
				'icon' => 'fa-pencil-alt',
				'command' => 'edit upload directory',
				'command_title' => 'Edit <b>Upload Directory</b>',
				'dynamic' => true,
				'addon' => false,
				'target' => 'files/directories',
				'permission' => 'can_edit_upload_directories'
			),
			'viewMembers' => array(
				'icon' => 'fa-eye',
				'command' => 'view members',
				'command_title' => 'View <b>Members</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'members',
				'permission' => 'can_access_members'
			),
			'viewMembersIn' => array(
				'icon' => 'fa-eye',
				'command' => 'view members in',
				'command_title' => 'View <b>Members</b> in <i>[member group]</i>',
				'dynamic' => true,
				'addon' => false,
				'target' => 'members/view',
				'permission' => 'can_access_members'
			),
			'editMember' => array(
				'icon' => 'fa-pencil-alt',
				'command' => 'edit member titled',
				'command_title' => 'Edit <b>Member</b> titled <i>[name]</i>',
				'dynamic' => true,
				'addon' => false,
				'target' => 'members/edit',
				'permission' => 'can_edit_members'
			),
			'editMemberGroup' => array(
				'icon' => 'fa-pencil-alt',
				'command' => 'edit member group titled',
				'command_title' => 'Edit <b>Member Group</b> titled <i>[group]</i>',
				'dynamic' => true,
				'addon' => false,
				'target' => 'members/group',
				'permission' => 'can_edit_roles'
			),
			'viewAddons' => array(
				'icon' => 'fa-eye',
				'command' => 'view addons',
				'command_title' => 'View <b>Add-ons</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'addons',
				'permission' => 'can_access_addons'
			),
			'viewChannels' => array(
				'icon' => 'fa-eye',
				'command' => 'view channels',
				'command_title' => 'View <b>Channels</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'channels',
				'permission' => 'can_admin_channels'
			),
			'viewChannelFields' => array(
				'icon' => 'fa-eye',
				'command' => 'view channel fields',
				'command_title' => 'View <b>Channel Fields</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'fields',
				'permission' => ['can_create_channel_fields', 'can_edit_channel_fields']
			),
			'createChannelField' => array(
				'icon' => 'fa-plus',
				'command' => 'create new publish channel field',
				'command_title' => 'Create <b>Channel Field</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'fields/create',
				'permission' => 'can_create_channel_fields'
			),
			'createChannel' => array(
				'icon' => 'fa-plus',
				'command' => 'create new publish channel',
				'command_title' => 'Create <b>Channel</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'channels/create',
				'permission' => 'can_create_channels'
			),
			'editChannel' => array(
				'icon' => 'fa-pencil-alt',
				'command' => 'edit channel titled',
				'command_title' => 'Edit <b>Channel</b> titled <i>[channel]</i>',
				'dynamic' => true,
				'addon' => false,
				'target' => 'channels/edit',
				'permission' => 'can_edit_channels'
			),
			'viewTemplates' => array(
				'icon' => 'fa-eye',
				'command' => 'view templates',
				'command_title' => 'View <b>Templates</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'design',
				'permission' => 'can_access_design'
			),
			'viewTemplatesIn' => array(
				'icon' => 'fa-eye',
				'command' => 'view templates in',
				'command_title' => 'View <b>Templates</b> in <i>[group]</i>',
				'dynamic' => true,
				'addon' => false,
				'target' => 'templates/view',
				'permission' => 'can_access_design'
			),
			'createTemplateGroup' => array(
				'icon' => 'fa-plus',
				'command' => 'create new publish template group',
				'command_title' => 'Create <b>Template Group</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'design/group/create',
				'permission' => 'can_create_template_groups'
			),
			'createTemplateIn' => array(
				'icon' => 'fa-plus',
				'command' => 'create new publish template in',
				'command_title' => 'Create <b>Template</b> in <i>[group]</i>',
				'dynamic' => true,
				'addon' => false,
				'target' => 'templates/create',
				'permission' => 'can_access_design'
			),
			'editTemplate' => array(
				'icon' => 'fa-pencil-alt',
				'command' => 'edit template titled',
				'command_title' => 'Edit <b>Template</b> titled <i>[template]</i>',
				'dynamic' => true,
				'addon' => false,
				'target' => 'templates/edit',
				'permission' => 'can_access_design'
			),
			'editTemplateGroup' => array(
				'icon' => 'fa-pencil-alt',
				'command' => 'edit template group titled',
				'command_title' => 'Edit <b>Template Group</b> titled <i>[group]</i>',
				'dynamic' => true,
				'addon' => false,
				'target' => 'templates/group',
				'permission' => 'can_edit_template_groups'
			),
			'systemSettingsGeneral' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit general settings',
				'command_title' => 'Edit <b>General</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/general',
				'permission' => 'can_access_sys_prefs'
			),
			'systemSettingsUrls' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit general settings url and path settings',
				'command_title' => 'Edit General Settings &raquo; <b>URL and Path</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/urls',
				'permission' => 'can_access_sys_prefs'
			),
			'systemSettingsEmail' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit general settings email settings',
				'command_title' => 'Edit General Settings &raquo; <b>Outgoing Email</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/email',
				'permission' => 'can_access_sys_prefs'
			),
			'systemSettingsDebugging' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit general settings debugging settings',
				'command_title' => 'Edit General Settings &raquo; <b>Debugging</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/debug-output',
				'permission' => 'can_access_sys_prefs'
			),
			'systemSettingsContentDesign' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit content and design settings',
				'command_title' => 'Edit <b>Content & Design</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/content-design',
				'permission' => 'can_access_sys_prefs'
			),
			'systemSettingsComments' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit content and design comment settings',
				'command_title' => 'Edit Content & Design &raquo; <b>Comment</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/comments',
				'permission' => 'can_access_sys_prefs'
			),
			'systemSettingsButtons' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit content and design html buttons settings',
				'command_title' => 'Edit Content & Design &raquo; <b>HTML Buttons</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/buttons',
				'permission' => 'can_access_sys_prefs'
			),
			'systemSettingsTemplate' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit content and design template settings',
				'command_title' => 'Edit Content & Design &raquo; <b>Template</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/template',
				'permission' => 'can_access_sys_prefs'
			),
			'systemSettingsHitTracking' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit content and design hit tracking settings',
				'command_title' => 'Edit Content & Design &raquo; <b>Hit Tracking</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/hit-tracking',
				'permission' => 'can_access_sys_prefs'
			),
			'systemSettingsWordCensoring' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit content and design word censoring settings',
				'command_title' => 'Edit Content & Design &raquo; <b>Word Censoring</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/word-censor',
				'permission' => 'can_access_sys_prefs'
			),
			'systemSettingsMenuManager' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit content and design menu manager settings',
				'command_title' => 'Edit Content & Design &raquo; <b>Menu Manager</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/menu-manager',
				'permission' => 'can_access_sys_prefs'
			),
			'systemSettingsMembers' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit members settings',
				'command_title' => 'Edit <b>Members</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/members',
				'permission' => 'access_sys_prefs'
			),
			'systemSettingsMessages' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit members messages settings',
				'command_title' => 'Edit Members &raquo; <b>Messages</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/messages',
				'permission' => 'can_access_sys_prefs'
			),
			'systemSettingsAvatars' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit members avatars settings',
				'command_title' => 'Edit Members &raquo; <b>Avatars</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/avatars',
				'permission' => 'can_access_sys_prefs'
			),
			'systemSettingsSecurityPrivacy' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit security and privacy settings',
				'command_title' => 'Edit <b>Security & Privacy</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/security-privacy',
				'permission' => 'can_access_security_settings'
			),
			'systemSettingsAccessThrottling' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit security and privacy access throttling settings',
				'command_title' => 'Edit Security & Privacy &raquo; <b>Access Throttling</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/throttling',
				'permission' => 'can_access_security_settings'
			),
			'systemSettingsCaptcha' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit security and privacy captcha settings',
				'command_title' => 'Edit Security & Privacy &raquo; <b>CAPTCHA</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/captcha',
				'permission' => 'can_access_security_settings'
			),
			'systemSettingsConsentRequests' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit security and privacy consent requests settings',
				'command_title' => 'Edit Security & Privacy &raquo; <b>Consent Requests</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/consents',
				'permission' => 'can_manage_consents'
			),
			'systemUtilitiesCommunicate' => array(
				'icon' => 'fa-hammer',
				'command' => 'system utilities communicate',
				'command_title' => 'System Utilities &raquo; <b>Communicate</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'utilities/communicate',
				'permission'	=> 'can_access_comm'
			),
			'systemUtilitiesCommunicateSent' => array(
				'icon' => 'fa-hammer',
				'command' => 'system utilities communicate sent',
				'command_title' => 'System Utilities &raquo; Communicate &raquo; <b>Sent</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'utilities/communicate/sent',
				'permission'	=> 'can_access_comm'
			),
			'systemUtilitiesTranslation' => array(
				'icon' => 'fa-hammer',
				'command' => 'system utilities translation',
				'command_title' => 'System Utilities &raquo; <b>CP Translation</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'utilities/translate',
				'permission'	=> 'can_access_translate'
			),
			'systemUtilitiesPHPInfo' => array(
				'icon' => 'fa-hammer',
				'command' => 'system utilities phpinfo',
				'command_title' => 'System Utilities &raquo; <b>PHP Info</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'utilities/php',
				'permission'	=> 'can_access_utilities'
			),
			'systemUtilitiesExtensions' => array(
				'icon' => 'fa-hammer',
				'command' => 'system utilities extensions',
				'command_title' => 'System Utilities &raquo; <b>Extensions</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'utilities/extensions',
				'permission'	=> 'can_access_utilities'
			),
			'systemUtilitiesFileConverter' => array(
				'icon' => 'fa-hammer',
				'command' => 'system utilities member tools import file converter',
				'command_title' => 'System Utilities &raquo; Member Tools &raquo; <b>File Converter</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'utilities/import-converter',
				'permission'	=> 'can_access_import'
			),
			'systemUtilitiesMemberImport' => array(
				'icon' => 'fa-hammer',
				'command' => 'system utilities member tools member import',
				'command_title' => 'System Utilities &raquo; Member Tools &raquo; <b>Member Import</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'utilities/member-import',
				'permission'	=> 'can_access_import'
			),
			'systemUtilitiesMassNotificationExport' => array(
				'icon' => 'fa-hammer',
				'command' => 'system utilities member tools mass notification export email addresses',
				'command_title' => 'System Utilities &raquo; Member Tools &raquo; <b>Mass Notification Export</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'utilities/export-email-addresses',
				'permission'	=> 'can_access_import'
			),
			'systemUtilitiesBackupUtility' => array(
				'icon' => 'fa-hammer',
				'command' => 'system utilities database backup utility',
				'command_title' => 'System Utilities &raquo; Database &raquo; <b>Backup Utility</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'utilities/db-backup',
				'permission'	=> 'can_access_sql_manager'
			),
			'systemUtilitiesSQLManager' => array(
				'icon' => 'fa-hammer',
				'command' => 'system utilities database sql manager',
				'command_title' => 'System Utilities &raquo; Database &raquo; <b>SQL Manager</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'utilities/sql',
				'permission'	=> 'can_access_sql_manager'
			),
			'systemUtilitiesQueryForm' => array(
				'icon' => 'fa-hammer',
				'command' => 'system utilities database sql query form',
				'command_title' => 'System Utilities &raquo; Database &raquo; <b>SQL Query Form</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'utilities/query',
				'permission'	=> 'can_access_sql_manager'
			),
			'systemUtilitiesCacheManager' => array(
				'icon' => 'fa-hammer',
				'command' => 'system utilities data operations clear cache manager',
				'command_title' => 'System Utilities &raquo; Data Operations &raquo; <b>Cache Manager</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'utilities/cache',
				'permission'	=> 'can_access_data'
			),
			'systemUtilitiesSearchReindex' => array(
				'icon' => 'fa-hammer',
				'command' => 'system utilities data operations search reindex',
				'command_title' => 'System Utilities &raquo; Data Operations &raquo; <b>Search Reindex</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'utilities/reindex',
				'permission'	=> 'can_access_data'
			),
			'systemUtilitiesStatistics' => array(
				'icon' => 'fa-hammer',
				'command' => 'system utilities data operations statistics',
				'command_title' => 'System Utilities &raquo; Data Operations &raquo; <b>Statistics</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'utilities/stats',
				'permission'	=> 'can_access_data'
			),
			'systemUtilitiesSearchAndReplace' => array(
				'icon' => 'fa-hammer',
				'command' => 'system utilities data operations search and replace',
				'command_title' => 'System Utilities &raquo; Data Operations &raquo; <b>Search and Replace</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'utilities/sandr',
				'permission'	=> 'can_access_data'
			),
			'switchTheme' => array(
				'icon' => 'fa-random',
				'command' => 'switch theme',
				'command_title' => 'Switch <b>Theme</b> to <i>[theme]</i>',
				'dynamic' => true,
				'addon' => false,
				'target' => 'themes/switch'
			)
		)
	);

	/**
	 * Is the menu empty?
	 *
	 * @return bool Is empty?
	 */
	public function hasItems()
	{
		return ! empty(self::$items);
	}


	/**
	 * Get all items in the menu
	 *
	 * @return array of Link|Submenu Objects
	 */
	public function getItems()
	{
		$items = ee()->cache->file->get('jumpmenu/'.ee()->session->getMember()->getId());
		if (empty($items))
		{
			$this->primeCache();
		}
		return self::$items;
	}

	/**
	 * clear all caches
	 * for now we're just forcing file driver, but that might change later
	 */

	public function clearAllCaches()
	{
		ee()->cache->file->clean('jumpmenu');
	}

	/**
	 * Returns items and rebuilds item list and caches it
	 */
	public function primeCache()
	{
		ee()->cache->file->delete('jumpmenu/'.ee()->session->getMember()->getId());

		$items = self::$items;

		if (!ee('Permission')->isSuperAdmin())
		{
			foreach ($items[1] as $name => $item)
			{
				if (!empty($item['permission']))
				{
					if (!ee('Permission')->hasAny($item['permission']))
					{
						unset($items[1][$name]);
					}
				}
			}
		}

		if (ee('Permission')->can('access_addons'))
		{
			$addon_infos = ee('Addon')->all();
			$assigned_modules = ee()->session->getMember()->getAssignedModules()->pluck('module_name');
			foreach ($addon_infos as $name => $info)
			{

				if ($info->hasModule() && !in_array($info->getModuleClass(), $assigned_modules))
				{
					continue;
				}

				if ($info->get('built_in') || ! $info->isInstalled() || ! $info->get('settings_exist'))
				{
					continue;
				}

				if ($info->hasExtension() || $info->hasControlPanel())
				{
					// Create a jump to the add-on itself.
					$items[1]['addon_' . $name] = array(
						'icon' => 'fa-puzzle-piece',
						'command' => 'addon add-on ' . $name,
						'command_title' => 'Add-on: ' . $info->getName(),
						'dynamic' => false,
						'addon' => true,
						'target' => 'addons/settings/' . $name
					);
				}

				if ( ! $info->hasJumpMenu())
				{
					continue;
				}

				$items[1] = array_merge($items[1], $info->getJumps());
			}
		}

		// Cache our items. We're bypassing the checks for the default
		// cache driver because we want this to be cached and working
		// even if the dev has set caching to disabled.
		ee()->cache->file->save('jumpmenu/'.ee()->session->getMember()->getId(), $items, 3600);

		// Assign our combined item list back to our static variable.
		self::$items = $items;
	}
}
