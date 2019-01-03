<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Utilities;

use CP_Controller;
use EllisLab\ExpressionEngine\Library\CP;

/**
 * Utilities Controller
 */
class Utilities extends CP_Controller {

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		ee('CP/Alert')->makeDeprecationNotice()->now();

		if ( ! $this->cp->allowed_group('can_access_utilities'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		ee()->lang->loadfile('utilities');

		$this->generateSidebar();

		ee()->view->header = array(
			'title' => lang('system_utilities')
		);

		// Some garbage collection
		ExportEmailAddresses::garbageCollect();
	}

	protected function generateSidebar($active = NULL)
	{
		$sidebar = ee('CP/Sidebar')->make();

		if (ee()->cp->allowed_group('can_access_comm'))
		{
			$left_nav = $sidebar->addHeader(lang('communicate'), ee('CP/URL')->make('utilities/communicate'));

			if (ee()->cp->allowed_group('can_send_cached_email'))
			{
				$left_nav->addBasicList()
					->addItem(lang('sent'), ee('CP/URL')->make('utilities/communicate/sent'));
			}
		}

		if (ee()->cp->allowed_group('can_access_translate'))
		{
			$langauge_list = $sidebar->addHeader(lang('cp_translation'))
				->addBasicList();
			$default_language = ee()->config->item('deft_lang') ?: 'english';
			$languages = array();
			foreach (ee()->lang->language_pack_names() as $key => $value)
			{
				$menu_title = $value;
				$url = ee('CP/URL')->make('utilities/translate/' . $key);
				if ($key == $default_language)
				{
					$menu_title .= ' (' . lang('default') . ')';
					// Make the default language first
					$languages = array_merge(array($menu_title => $url), $languages);
					continue;
				}
				$languages[$menu_title] = $url;
			}
			foreach ($languages as $menu_title => $url)
			{
				$langauge_list->addItem($menu_title, $url);
			}
		}

		$sidebar->addHeader(lang('php_info'), ee('CP/URL')->make('utilities/php'))
			->urlIsExternal();

		if (ee()->cp->allowed_group('can_access_addons') && ee()->cp->allowed_group('can_admin_addons'))
		{
			$sidebar->addHeader(lang('debug_extensions'), ee('CP/URL')->make('utilities/extensions'));
		}

		if (ee('Permission')->hasAny('can_access_import', 'can_access_members'))
		{
			$member_tools = $sidebar->addHeader(lang('member_tools'))
				->addBasicList();
			if (ee('Permission')->has('can_access_import'))
			{
				$member_tools->addItem(lang('file_converter'), ee('CP/URL')->make('utilities/import-converter'));
				$member_tools->addItem(lang('member_import'), ee('CP/URL')->make('utilities/member-import'));
			}
			if (ee('Permission')->has('can_access_members'))
			{
				$member_tools->addItem(lang('mass_notification_export'), ee('CP/URL')->make('utilities/export-email-addresses'));
			}
		}

		if (ee()->cp->allowed_group('can_access_sql_manager'))
		{
			$db_list = $sidebar->addHeader(lang('database'))->addBasicList();
			$db_list->addItem(lang('backup_utility'), ee('CP/URL')->make('utilities/db-backup'));
			$db_list->addItem(lang('sql_manager_abbr'), ee('CP/URL')->make('utilities/sql'));
			$db_list->addItem(lang('query_form'), ee('CP/URL')->make('utilities/query'));
		}

		if (ee()->cp->allowed_group('can_access_data'))
		{
			$data_list = $sidebar->addHeader(lang('data_operations'))
			->addBasicList();
			$data_list->addItem(lang('cache_manager'), ee('CP/URL')->make('utilities/cache'));
			$data_list->addItem(lang('statistics'), ee('CP/URL')->make('utilities/stats'));
			$data_list->addItem(lang('search_and_replace'), ee('CP/URL')->make('utilities/sandr'));
		}
	}

	/**
	 * Index
	 *
	 * @access	public
	 * @return	void
	 */
	public function index()
	{
		// Will redirect based on permissions later
		ee()->functions->redirect(ee('CP/URL')->make('utilities/communicate'));
	}
}
// END CLASS

// EOF
