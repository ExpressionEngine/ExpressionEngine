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

use ExpressionEngine\Library\Advisor;
use ExpressionEngine\Library\CP\Table;

/**
 * Debug Tools
*/
class DebugTools extends Utilities
{
    /**
     * Deny access from non-superadmins
     */
    public function __construct()
    {
        parent::__construct();

        if (ee()->session->userdata('group_id') != 1) {
            show_error(lang('unauthorized_access'), 403);
        }
    }

    /**
     * Debug tools overview
     *
     * @return String
     */
    public function index()
    {
        ee()->view->cp_page_title = lang('debug_tools');
        ee()->view->cp_heading = lang('debug_tools');

        $vars = [];

        $templateAdvisor = new Advisor\TemplateAdvisor();
        $vars['bad_tags_count'] = $templateAdvisor->getBadTagCount();
        $vars['duplicate_template_groups_count'] = $templateAdvisor->getDuplicateTemplateGroupsCount();

        $ftAdvisor = new Advisor\FieldtypeAdvisor();
        $vars['missing_fieldtype_count'] = $ftAdvisor->getMissingFieldtypeCount();

        ee()->view->cp_breadcrumbs = array(
            '' => lang('debug_tools')
        );

        return ee()->cp->render('utilities/debug-tools/index', $vars);
    }

    public function debugTags()
    {
        ee()->lang->load('design');

        ee()->view->cp_page_title = lang('debug_tools_debug_tags');

        $templateAdvisor = new Advisor\TemplateAdvisor();

        $vars = [];
        $vars['tags'] = $templateAdvisor->getAllTags();

        // Loop through the tamplate tags to generate table data
        $data = [];
        foreach ($vars['tags'] as $tag_name => $tag) {
            $modal_name = str_replace(':', '', trim($tag_name, "{}"));
            $contents = ee('View')->make('utilities/debug-tools/modals/template_list')->render($tag);

            // This generates the modal, and adds it to the DOM
            $modal_vars = array(
                'name' => $modal_name,
                'contents' => $contents
            );
            $modal_html = ee('View')->make('ee:_shared/modal')->render($modal_vars);
            // Add the modal to the DOM
            ee('CP/Modal')->addModal($modal_name, $modal_html);

            $data[] = array(
                $tag['installed'] ? '✔️' : '❌',
                '<a href="" class="m-link" rel="' . $modal_name . '">' . $tag_name . '</a>',
                $tag['addon_name'],
                $tag['count'],
            );
        }

        // Specify other options
        $table = ee('CP/Table', array(
            'autosort' => true,
            'autosearch' => true,
            'sort_col' => 'debug_tools_installed',
            'sort_dir' => 'desc',
        ));

        $table->setColumns(
            array(
                'debug_tools_installed',
                'debug_tools_tag' => ['encode' => false],
                'debug_tools_addon',
                'debug_tools_count')
        );

        $table->setData($data);

        $base_url = ee('CP/URL', 'utilities/debug-tools/debug-tags');
        $vars['table'] = $table->viewData($base_url);

        $vars['pagination'] = ee('CP/Pagination', count($data))
            ->currentPage($vars['table']['page'])
            ->perPage($vars['table']['limit'])
            ->render($base_url);

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('utilities/debug-tools')->compile() => lang('debug_tools'),
            '' => lang('debug_tools_debug_tags')
        );

        return ee()->cp->render('utilities/debug-tools/show_tags', $vars);
    }

    public function debugFieldtypes()
    {
        ee()->view->cp_page_title = lang('debug_tools_fieldtypes');

        $ftAdvisor = new Advisor\FieldtypeAdvisor();

        $vars = [];
        $vars['used_fieldtypes'] = $ftAdvisor->getUsedFieldtypes();
        $vars['unused_fieldtypes'] = $ftAdvisor->getUnusedFieldtypes();
        $vars['missing_fieldtypes'] = $ftAdvisor->getMissingFieldtypes();
        $vars['missing_fieldtype_count'] = $ftAdvisor->getMissingFieldtypeCount();

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('utilities/debug-tools')->compile() => lang('debug_tools'),
            '' => lang('debug_tools_fieldtypes')
        );

        return ee()->cp->render('utilities/debug-tools/missing_fieldtypes', $vars);
    }

    public function duplicateTemplateGroups()
    {
        ee()->view->cp_page_title = lang('debug_tools_debug_duplicate_template_groups');

        $templateAdvisor = new Advisor\TemplateAdvisor();

        $vars = [];
        $table = ee('CP/Table', array('autosort' => false));

        $table->setColumns(
            array(
                'group_id',
                'group_name',
                'manage' => array(
                    'type' => Table::COL_TOOLBAR
                )
            )
        );

        $data = [];
        $duplicates = $templateAdvisor->getDuplicateTemplateGroups();
        if (count($duplicates) > 0) {
            foreach ($duplicates as $row) {
                $toolbar = array(
                    'edit' => array(
                        'href' => ee('CP/URL')->make('design/group/edit/' . $row['group_name'] . '/' . $row['group_id']),
                        'title' => lang('edit')
                    ),
                    'remove' => array(
                        'href' => '#',
                        'class' => 'm-link',
                        'data-confirm' => lang('template_group') . ': <b>' . $row['group_name'] . '</b>, ID: <b>' . $row['group_id'] . '</b>',
                        'data-group_id' => $row['group_id'],
                        'title' => lang('remove'),
                        'rel' => 'modal-confirm-delete-template-group'
                    )
                );
                $column = [
                    $row['group_id'],
                    $row['group_name'],
                    ['toolbar_items' => $toolbar]
                ];
                $data[] = [
                    'attrs' => [],
                    'columns' => $column
                ];
            }
        }

        $table->setData($data);

        if (!empty($data)) {
            ee('CP/Alert')
                ->makeInline()
                ->addToBody(lang('back_up_db_and_templates'))
                ->asImportant()
                ->now();
        }

        $vars['form_url'] = ee('CP/URL')->make('design/group/remove');

        $base_url = ee('CP/URL', 'utilities/debug-tools/duplicate-template-groups');
        $vars['table'] = $table->viewData($base_url);

        ee()->cp->add_js_script(array(
            'file' => array(
                'cp/confirm_remove',
                'cp/design/manager'
            ),
        ));

        ee()->javascript->output("
        $(document).ready(function () {
            $('[rel=modal-confirm-delete-template-group]').click(function (e) {
                var modalIs = '.' + $(this).attr('rel');
        
                $(modalIs + ' .checklist').html(''); // Reset it
                $(modalIs + ' .checklist').append('<li>' + $(this).data('confirm') + '</li>');
                $(modalIs + ' input[name=group_id]').val($(this).data('group_id'));
        
                e.preventDefault();
            })
        });
        ");

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('utilities/debug-tools')->compile() => lang('debug_tools'),
            '' => lang('debug_tools_debug_duplicate_template_groups')
        );

        return ee()->cp->render('utilities/debug-tools/duplicate_template_groups', $vars);
    }
}
// END CLASS

// EOF
