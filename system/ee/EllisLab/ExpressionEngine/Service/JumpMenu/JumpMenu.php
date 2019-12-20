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
				'target' => 'publish/view'
			),
			'createEntryIn' => array(
				'icon' => 'fa-plus',
				'command' => 'create new publish entry in',
				'command_title' => 'Create <b>Entry</b> in <i>[channel]</i>',
				'dynamic' => true,
				'addon' => false,
				'target' => 'publish/create'
			),
			'editEntry' => array(
				'icon' => 'fa-pencil-alt',
				'command' => 'edit entry titled',
				'command_title' => 'Edit <b>Entry</b> titled <i>[title]</i>',
				'dynamic' => true,
				'addon' => false,
				'target' => 'publish/edit'
			),
			'createMember' => array(
				'icon' => 'fa-plus',
				'command' => 'create new publish member',
				'command_title' => 'Create <b>Member</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'members/create'
			),
			'createMemberGroup' => array(
				'icon' => 'fa-plus',
				'command' => 'create new publish member group',
				'command_title' => 'Create <b>Member Group</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'members/groups/create'
			),
			'createCategoryIn' => array(
				'icon' => 'fa-plus',
				'command' => 'create new publish category in',
				'command_title' => 'Create <b>Category</b> in <i>[category group]</i>',
				'dynamic' => true,
				'addon' => false,
				'target' => 'categories/create'
			),
			'editCategory' => array(
				'icon' => 'fa-pencil-alt',
				'command' => 'edit category titled',
				'command_title' => 'Edit <b>Category</b> titled <i>[category]</i>',
				'dynamic' => true,
				'addon' => false,
				'target' => 'categories/edit'
			),
			'viewFiles' => array(
				'icon' => 'fa-eye',
				'command' => 'view all files',
				'command_title' => 'View <b>All Files</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'files'
			),
			'viewFilesIn' => array(
				'icon' => 'fa-eye',
				'command' => 'view files in',
				'command_title' => 'View <b>Files</b> in <i>[upload directory]</i>',
				'dynamic' => true,
				'addon' => false,
				'target' => 'files/view'
			),
			'editUploadDirectory' => array(
				'icon' => 'fa-pencil-alt',
				'command' => 'edit upload directory',
				'command_title' => 'Edit <b>Upload Directory</b>',
				'dynamic' => true,
				'addon' => false,
				'target' => 'files/directories'
			),
			'viewMembers' => array(
				'icon' => 'fa-eye',
				'command' => 'view members',
				'command_title' => 'View <b>Members</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'members'
			),
			'viewMembersIn' => array(
				'icon' => 'fa-eye',
				'command' => 'view members in',
				'command_title' => 'View <b>Members</b> in <i>[member group]</i>',
				'dynamic' => true,
				'addon' => false,
				'target' => 'members/view'
			),
			'editMember' => array(
				'icon' => 'fa-pencil-alt',
				'command' => 'edit member titled',
				'command_title' => 'Edit <b>Member</b> titled <i>[name]</i>',
				'dynamic' => true,
				'addon' => false,
				'target' => 'members/edit'
			),
			'editMemberGroup' => array(
				'icon' => 'fa-pencil-alt',
				'command' => 'edit member group titled',
				'command_title' => 'Edit <b>Member Group</b> titled <i>[group]</i>',
				'dynamic' => true,
				'addon' => false,
				'target' => 'members/group'
			),
			'viewAddons' => array(
				'icon' => 'fa-eye',
				'command' => 'view addons',
				'command_title' => 'View <b>Add-ons</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'addons'
			),
			'viewChannels' => array(
				'icon' => 'fa-eye',
				'command' => 'view channels',
				'command_title' => 'View <b>Channels</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'channels'
			),
			'viewChannelFields' => array(
				'icon' => 'fa-eye',
				'command' => 'view channel fields',
				'command_title' => 'View <b>Channel Fields</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'fields'
			),
			'createChannelField' => array(
				'icon' => 'fa-plus',
				'command' => 'create new publish channel field',
				'command_title' => 'Create <b>Channel Field</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'fields/create'
			),
			'createChannel' => array(
				'icon' => 'fa-plus',
				'command' => 'create new publish channel',
				'command_title' => 'Create <b>Channel</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'channels/create'
			),
			'editChannel' => array(
				'icon' => 'fa-pencil-alt',
				'command' => 'edit channel titled',
				'command_title' => 'Edit <b>Channel</b> titled <i>[channel]</i>',
				'dynamic' => true,
				'addon' => false,
				'target' => 'channels/edit'
			),
			'viewTemplates' => array(
				'icon' => 'fa-eye',
				'command' => 'view templates',
				'command_title' => 'View <b>Templates</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'design'
			),
			'viewTemplatesIn' => array(
				'icon' => 'fa-eye',
				'command' => 'view templates in',
				'command_title' => 'View <b>Templates</b> in <i>[group]</i>',
				'dynamic' => true,
				'addon' => false,
				'target' => 'templates/view'
			),
			'createTemplateGroup' => array(
				'icon' => 'fa-plus',
				'command' => 'create new publish template group',
				'command_title' => 'Create <b>Template Group</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'design/group/create'
			),
			'createTemplateIn' => array(
				'icon' => 'fa-plus',
				'command' => 'create new publish template in',
				'command_title' => 'Create <b>Template</b> in <i>[group]</i>',
				'dynamic' => true,
				'addon' => false,
				'target' => 'templates/create'
			),
			'editTemplate' => array(
				'icon' => 'fa-pencil-alt',
				'command' => 'edit template titled',
				'command_title' => 'Edit <b>Template</b> titled <i>[template]</i>',
				'dynamic' => true,
				'addon' => false,
				'target' => 'templates/edit'
			),
			'editTemplateGroup' => array(
				'icon' => 'fa-pencil-alt',
				'command' => 'edit template group titled',
				'command_title' => 'Edit <b>Template Group</b> titled <i>[group]</i>',
				'dynamic' => true,
				'addon' => false,
				'target' => 'templates/group'
			),
			'systemSettingsGeneral' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit general settings',
				'command_title' => 'Edit <b>General</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/general'
			),
			'systemSettingsUrls' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit general settings url and path settings',
				'command_title' => 'Edit General Settings &raquo; <b>URL and Path</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/urls'
			),
			'systemSettingsEmail' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit general settings email settings',
				'command_title' => 'Edit General Settings &raquo; <b>Outgoing Email</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/email'
			),
			'systemSettingsDebugging' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit general settings debugging settings',
				'command_title' => 'Edit General Settings &raquo; <b>Debugging</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/debug-output'
			),
			'systemSettingsContentDesign' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit content and design settings',
				'command_title' => 'Edit <b>Content & Design</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/content-design'
			),
			'systemSettingsComments' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit content and design comment settings',
				'command_title' => 'Edit Content & Design &raquo; <b>Comment</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/comments'
			),
			'systemSettingsButtons' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit content and design html buttons settings',
				'command_title' => 'Edit Content & Design &raquo; <b>HTML Buttons</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/buttons'
			),
			'systemSettingsTemplate' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit content and design template settings',
				'command_title' => 'Edit Content & Design &raquo; <b>Template</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/template'
			),
			'systemSettingsHitTracking' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit content and design hit tracking settings',
				'command_title' => 'Edit Content & Design &raquo; <b>Hit Tracking</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/hit-tracking'
			),
			'systemSettingsWordCensoring' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit content and design word censoring settings',
				'command_title' => 'Edit Content & Design &raquo; <b>Word Censoring</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/word-censor'
			),
			'systemSettingsMenuManager' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit content and design menu manager settings',
				'command_title' => 'Edit Content & Design &raquo; <b>Menu Manager</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/menu-manager'
			),
			'systemSettingsMembers' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit members settings',
				'command_title' => 'Edit <b>Members</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/members'
			),
			'systemSettingsMessages' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit members messages settings',
				'command_title' => 'Edit Members &raquo; <b>Messages</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/messages'
			),
			'systemSettingsAvatars' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit members avatars settings',
				'command_title' => 'Edit Members &raquo; <b>Avatars</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/avatars'
			),
			'systemSettingsSecurityPrivacy' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit security and privacy settings',
				'command_title' => 'Edit <b>Security & Privacy</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/security-privacy'
			),
			'systemSettingsAccessThrottling' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit security and privacy access throttling settings',
				'command_title' => 'Edit Security & Privacy &raquo; <b>Access Throttling</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/throttling'
			),
			'systemSettingsCaptcha' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit security and privacy captcha settings',
				'command_title' => 'Edit Security & Privacy &raquo; <b>CAPTCHA</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/captcha'
			),
			'systemSettingsConsentRequests' => array(
				'icon' => 'fa-wrench',
				'command' => 'edit security and privacy consent requests settings',
				'command_title' => 'Edit Security & Privacy &raquo; <b>Consent Requests</b> Settings',
				'dynamic' => false,
				'addon' => false,
				'target' => 'settings/consents'
			),
			'systemUtilitiesCommunicate' => array(
				'icon' => 'fa-hammer',
				'command' => 'system utilities communicate',
				'command_title' => 'System Utilities &raquo; <b>Communicate</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'utilities/communicate'
			),
			'systemUtilitiesCommunicateSent' => array(
				'icon' => 'fa-hammer',
				'command' => 'system utilities communicate sent',
				'command_title' => 'System Utilities &raquo; Communicate &raquo; <b>Sent</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'utilities/communicate/sent'
			),
			'systemUtilitiesTranslation' => array(
				'icon' => 'fa-hammer',
				'command' => 'system utilities translation',
				'command_title' => 'System Utilities &raquo; <b>CP Translation</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'utilities/translate'
			),
			'systemUtilitiesPHPInfo' => array(
				'icon' => 'fa-hammer',
				'command' => 'system utilities phpinfo',
				'command_title' => 'System Utilities &raquo; <b>PHP Info</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'utilities/php'
			),
			'systemUtilitiesExtensions' => array(
				'icon' => 'fa-hammer',
				'command' => 'system utilities extensions',
				'command_title' => 'System Utilities &raquo; <b>Extensions</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'utilities/extensions'
			),
			'systemUtilitiesFileConverter' => array(
				'icon' => 'fa-hammer',
				'command' => 'system utilities member tools import file converter',
				'command_title' => 'System Utilities &raquo; Member Tools &raquo; <b>File Converter</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'utilities/import-converter'
			),
			'systemUtilitiesMemberImport' => array(
				'icon' => 'fa-hammer',
				'command' => 'system utilities member tools member import',
				'command_title' => 'System Utilities &raquo; Member Tools &raquo; <b>Member Import</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'utilities/member-import'
			),
			'systemUtilitiesMassNotificationExport' => array(
				'icon' => 'fa-hammer',
				'command' => 'system utilities member tools mass notification export email addresses',
				'command_title' => 'System Utilities &raquo; Member Tools &raquo; <b>Mass Notification Export</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'utilities/export-email-addresses'
			),
			'systemUtilitiesBackupUtility' => array(
				'icon' => 'fa-hammer',
				'command' => 'system utilities database backup utility',
				'command_title' => 'System Utilities &raquo; Database &raquo; <b>Backup Utility</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'utilities/db-backup'
			),
			'systemUtilitiesSQLManager' => array(
				'icon' => 'fa-hammer',
				'command' => 'system utilities database sql manager',
				'command_title' => 'System Utilities &raquo; Database &raquo; <b>SQL Manager</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'utilities/sql'
			),
			'systemUtilitiesQueryForm' => array(
				'icon' => 'fa-hammer',
				'command' => 'system utilities database sql query form',
				'command_title' => 'System Utilities &raquo; Database &raquo; <b>SQL Query Form</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'utilities/query'
			),
			'systemUtilitiesCacheManager' => array(
				'icon' => 'fa-hammer',
				'command' => 'system utilities data operations clear cache manager',
				'command_title' => 'System Utilities &raquo; Data Operations &raquo; <b>Cache Manager</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'utilities/cache'
			),
			'systemUtilitiesSearchReindex' => array(
				'icon' => 'fa-hammer',
				'command' => 'system utilities data operations search reindex',
				'command_title' => 'System Utilities &raquo; Data Operations &raquo; <b>Search Reindex</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'utilities/reindex'
			),
			'systemUtilitiesStatistics' => array(
				'icon' => 'fa-hammer',
				'command' => 'system utilities data operations statistics',
				'command_title' => 'System Utilities &raquo; Data Operations &raquo; <b>Statistics</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'utilities/stats'
			),
			'systemUtilitiesSearchAndReplace' => array(
				'icon' => 'fa-hammer',
				'command' => 'system utilities data operations search and replace',
				'command_title' => 'System Utilities &raquo; Data Operations &raquo; <b>Search and Replace</b>',
				'dynamic' => false,
				'addon' => false,
				'target' => 'utilities/sandr'
			),
			'destroyWebsite' => array(
				'icon' => 'fa-bomb',
				'command' => '💣',
				'command_title' => 'Destroy Website',
				'dynamic' => false,
				'addon' => false,
				'target' => 'jumps/destroy'
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
		return self::$items;
	}

	/**
	 * Returns cached items and rebuilds item list and caches it if necessary
	 * @param  boolean $flush Whether to force flush the jump menu cache.
	 */
	public function primeCache($flush = false)
	{
		if ($flush === true)
		{
			ee()->cache->file->delete('jumpmenu');
		}

		$items = ee()->cache->file->get('jumpmenu');

		if (empty($items))
		{
			$addon_infos = ee('Addon')->all();

			$items = self::$items;

			foreach ($addon_infos as $name => $info)
			{
				$info = ee('Addon')->get($name);

				if ($info->get('built_in') || ! $info->isInstalled() || ! $info->get('settings_exist'))
				{
					continue;
				}

				// Create a jump to the add-on itself.
				$items[1]['addon_' . $name] = array(
					'icon' => 'fa-puzzle-piece',
					'command' => 'addon add-on ' . $name,
					'command_title' => 'Add-on: ' . $info->getName(),
					'dynamic' => false,
					'addon' => true,
					'target' => 'addons/settings/' . $name
				);

				if ( ! $info->hasJumpMenu())
				{
					continue;
				}

				$items[1] = array_merge($items[1], $info->getJumps());
			}

			// Cache our items. We're bypassing the checks for the default
			// cache driver because we want this to be cached and working
			// even if the dev has set caching to disabled.
			ee()->cache->file->save('jumpmenu', $items, 60);
		}

		// Assign our combined item list back to our static variable.
		self::$items = $items;
	}
}
