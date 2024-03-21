<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Categories;

use CP_Controller;

/**
 * Abstract Categories
 */
abstract class AbstractCategories extends CP_Controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        ee('CP/Alert')->makeDeprecationNotice()->now();

        if (! ee('Permission')->has('can_admin_channels')) {
            show_error(lang('unauthorized_access'), 403);
        }

        // Allow AJAX requests for category editing
        if (AJAX_REQUEST && in_array(
            ee()->router->method,
            ['createCat', 'editCat', 'categoryGroupPublishField']
        )) {
            if (! ee('Permission')->hasAny(
                'can_create_categories',
                'can_edit_categories'
            )) {
                show_error(lang('unauthorized_access'), 403);
            }
        } else {
            if (! ee('Permission')->hasAny(
                'can_create_categories',
                'can_edit_categories',
                'can_delete_categories'
            )) {
                show_error(lang('unauthorized_access'), 403);
            }
        }

        ee()->lang->loadfile('content');
        ee()->lang->loadfile('admin_content');
        ee()->lang->loadfile('channel');
        ee()->load->library('form_validation');

        // This header is section-wide
        ee()->view->header = array(
            'title' => lang('category_manager')
        );

        ee()->javascript->set_global(
            'sets.importUrl',
            ee('CP/URL', 'channels/sets')->compile()
        );

        ee()->cp->add_js_script(array(
            'file' => array('cp/channel/menu'),
        ));
    }

    protected function generateSidebar($active = null)
    {
        $sidebar = ee('CP/Sidebar')->make();

        $header = $sidebar->addHeader(lang('category_groups'));

        $list = $header->addFolderList('categories')
            ->withNoResultsText(sprintf(lang('no_found'), lang('category_groups')));

        if (ee('Permission')->can('delete_categories')) {
            $list->withRemoveUrl(ee('CP/URL')->make('categories/groups/remove'))
                ->withRemovalKey('content_id');
        }

        $imported_groups = ee()->session->flashdata('imported_category_groups') ?: [];

        $groups = ee('Model')->get('CategoryGroup')
            ->filter('site_id', ee()->config->item('site_id'))
            ->order('group_name')
            ->all();

        foreach ($groups as $group) {
            $group_name = htmlentities($group->group_name, ENT_QUOTES, 'UTF-8');

            $item = $list->addItem(
                $group_name,
                ee('CP/URL')->make('categories/group/' . $group->getId())
            )->withIcon('tags');

            if (ee('Permission')->can('edit_categories')) {
                $item->withEditUrl(
                    ee('CP/URL')->make('categories/groups/edit/' . $group->getId())
                );
            }

            if (ee('Permission')->can('delete_categories')) {
                $item->withRemoveConfirmation(
                    lang('category_group') . ': <b>' . $group_name . '</b>'
                )->identifiedBy($group->getId());
            }

            if ($active == $group->getId()) {
                $item->isActive();
            }

            if (in_array($group->getId(), $imported_groups)) {
                $item->isSelected();
            }
        }

        $header->withButton(lang('new'), ee('CP/URL')->make('categories/groups/create'));
    }
}

// EOF
