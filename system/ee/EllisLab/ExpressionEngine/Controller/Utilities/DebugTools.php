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

use EllisLab\ExpressionEngine\Library\Advisor;

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

        $ftAdvisor = new Advisor\FieldtypeAdvisor();
        $vars['missing_fieldtype_count'] = $ftAdvisor->getMissingFieldtypeCount();

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
            $contents =  ee('View')->make('utilities/debug-tools/modals/template_list')->render($tag);

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
            'sort_col' => 'Installed',
            'sort_dir' => 'desc',
        ));

        $table->setColumns(
            array(
            'Installed',
            'Tag' => ['encode' => false],
            'Addon',
            'Count')
        );

        $table->setData($data);

        $base_url = ee('CP/URL', 'utilities/debug-tools/debug-tags');
        $vars['table'] = $table->viewData($base_url);

        $vars['pagination'] = ee('CP/Pagination', count($data))
            ->currentPage($vars['table']['page'])
            ->perPage($vars['table']['limit'])
            ->render($base_url);

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

        return ee()->cp->render('utilities/debug-tools/missing_fieldtypes', $vars);
    }


}
// END CLASS

// EOF
