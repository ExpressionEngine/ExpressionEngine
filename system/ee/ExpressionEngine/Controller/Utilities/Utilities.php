<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Utilities;

use CP_Controller;
use ExpressionEngine\Library\CP;

/**
 * Utilities Controller
 */
class Utilities extends CP_Controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        ee('CP/Alert')->makeDeprecationNotice()->now();

        //conditional fields sync should be accessed even if the user has no general utilities access
        if (! ee('Permission')->can('access_utilities') && ! ee('Permission')->can('edit_channel_fields')) {
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

    protected function generateSidebar($active = null)
    {
        $sidebar = ee('CP/Sidebar')->make();

        if (ee('Permission')->can('access_comm')) {
            $sidebar->addHeader(lang('communicate'));

            $url = ee('CP/URL')->make('utilities/communicate');
            $item = $sidebar->addItem(lang('send_email'), $url);
            if ($url->matchesTheRequestedURI() && ee()->uri->segment('4') != 'sent') {
                $item->isActive();
            }

            if (ee('Permission')->can('send_cached_email')) {
                $sidebar->addItem(lang('sent'), ee('CP/URL')->make('utilities/communicate/sent'));
            }
        }

        $sidebar->addHeader(lang('general_utilities'));

        if (ee('Permission')->can('access_translate')) {
            $translation_item = $sidebar->addItem(lang('cp_translations'), ee('CP/URL')->make('utilities/translate'));

            foreach (ee()->lang->language_pack_names() as $key => $value) {
                $url = ee('CP/URL')->make('utilities/translate/' . $key);

                if ($url->matchesTheRequestedURI()) {
                    $translation_item->isActive();
                }
            }
        }

        $sidebar->addItem(lang('php_info'), ee('CP/URL')->make('utilities/php'))
            ->urlIsExternal();

        if (ee('Permission')->can('access_addons') && ee('Permission')->can('admin_addons')) {
            $sidebar->addItem(lang('manage_extensions'), ee('CP/URL')->make('utilities/extensions'));
        }

        if (ee('Permission')->isSuperAdmin()) {
            $debug_tools = $sidebar->addHeader(lang('debug_tools'))
                ->addBasicList();
            $debug_tools->addItem(lang('debug_tools_overview'), ee('CP/URL')->make('utilities/debug-tools'));
            $debug_tools->addItem(lang('debug_tools_debug_duplicate_template_groups'), ee('CP/URL')->make('utilities/debug-tools/duplicate-template-groups'));
            $debug_tools->addItem(lang('debug_tools_debug_tags'), ee('CP/URL')->make('utilities/debug-tools/debug-tags'));
            $debug_tools->addItem(lang('debug_tools_fieldtypes'), ee('CP/URL')->make('utilities/debug-tools/debug-fieldtypes'));
        }

        if (ee('Permission')->hasAny('can_access_import', 'can_access_members')) {
            $member_tools = $sidebar->addHeader(lang('member_tools'))
                ->addBasicList();
            if (ee('Permission')->can('access_import')) {
                $member_tools->addItem(lang('file_converter'), ee('CP/URL')->make('utilities/import-converter'));
                $member_tools->addItem(lang('member_import'), ee('CP/URL')->make('utilities/member-import'));
            }
            if (ee('Permission')->can('access_members')) {
                $member_tools->addItem(lang('mass_notification_export'), ee('CP/URL')->make('utilities/export-email-addresses'));
            }
        }

        if (ee('Permission')->can('access_sql_manager')) {
            $db_list = $sidebar->addHeader(lang('database'))->addBasicList();
            $db_list->addItem(lang('backup_database'), ee('CP/URL')->make('utilities/db-backup'));
            $db_list->addItem(lang('sql_manager_abbr'), ee('CP/URL')->make('utilities/sql'));
            $url = ee('CP/URL')->make('utilities/query');
            $item = $db_list->addItem(lang('query_form'), $url);
            if ($url->matchesTheRequestedURI()) {
                $item->isActive();
            }
        }

        if (ee('Permission')->can('access_data')) {
            $data_list = $sidebar->addHeader(lang('data_operations'))
                ->addBasicList();
            $data_list->addItem(lang('cache_manager'), ee('CP/URL')->make('utilities/cache'));
            $data_list->addItem(lang('search_reindex'), ee('CP/URL')->make('utilities/reindex'));
            if (ee('Permission')->can('edit_channel_fields')) {
                // If we use a subpage like utilities/sync-conditional-fields/sync make it match the nav
                $sync_conditional_fields_url = ee('CP/URL')->make('utilities/sync-conditional-fields');
                $conditional_field_sync = $data_list->addItem(lang('sync_conditional_fields'), $sync_conditional_fields_url);
                if ($sync_conditional_fields_url->matchesTheRequestedURI()) {
                    $conditional_field_sync->isActive();
                }
            }
            $data_list->addItem(lang('update_file_usage'), ee('CP/URL')->make('utilities/file-usage'));
            $data_list->addItem(lang('statistics'), ee('CP/URL')->make('utilities/stats'));
            $data_list->addItem(lang('search_and_replace'), ee('CP/URL')->make('utilities/sandr'));
        }
    }

    /**
     * Index
     *
     * @access  public
     * @return  void
     */
    public function index()
    {
        // Will redirect based on permissions later
        ee()->functions->redirect(ee('CP/URL')->make('utilities/communicate'));
    }
}
// END CLASS

// EOF
