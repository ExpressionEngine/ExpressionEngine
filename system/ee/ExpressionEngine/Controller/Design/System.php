<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Design;

use ExpressionEngine\Controller\Design\AbstractDesign as AbstractDesignController;
use ExpressionEngine\Library\CP\Table;

/**
 * Design\System Controller
 */
class System extends AbstractDesignController
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        if (! ee('Permission')->can('admin_design')) {
            show_error(lang('unauthorized_access'), 403);
        }

        $this->stdHeader();
    }

    public function index()
    {
        $templates = ee('Model')->get('SpecialtyTemplate')
            ->filter('site_id', ee()->config->item('site_id'))
            ->filter('template_type', 'system')
            ->all();

        $vars = array();

        $base_url = ee('CP/URL')->make('design/system/');

        $table = ee('CP/Table', array('autosort' => true, 'limit' => 1024));
        $table->setColumns(
            array(
                'template',
                'manage' => array(
                    'type' => Table::COL_TOOLBAR
                ),
            )
        );

        $data = array();
        foreach ($templates as $template) {
            $edit_url = ee('CP/URL')->make('design/system/edit/' . $template->template_id);
            $data[] = array(
                array(
                    'content' => lang($template->template_name),
                    'href' => $edit_url
                ),
                array('toolbar_items' => array(
                    'edit' => array(
                        'href' => $edit_url,
                        'title' => lang('edit')
                    ),
                ))
            );
        }

        $table->setData($data);

        $vars['table'] = $table->viewData($base_url);
        $vars['form_url'] = $vars['table']['base_url'];

        $this->generateSidebar('messages');
        ee()->view->cp_page_title = lang('template_manager');
        ee()->view->cp_heading = lang('system_message_templates');

        ee()->view->cp_breadcrumbs = array(
            '' => lang('system_message_templates')
        );

        ee()->cp->render('design/system/index', $vars);
    }

    public function edit($template_id)
    {
        $template = ee('Model')->get('SpecialtyTemplate', $template_id)
            ->filter('site_id', ee()->config->item('site_id'))
            ->filter('template_type', 'system')
            ->first();

        if (! $template) {
            show_error(lang('error_no_template'));
        }

        if ($template->template_name == 'message_template') {
            ee('CP/Alert')->makeInline('message-warning')
                ->asWarning()
                ->cannotClose()
                ->addToBody(lang('message_template_warning'))
                ->now();
        }

        if (! empty($_POST)) {
            $template->template_data = ee()->input->post('template_data');
            $template->edit_date = ee()->localize->now;
            $template->last_author_id = ee()->session->userdata('member_id');
            $template->save();

            $alert = ee('CP/Alert')->makeInline('template-form')
                ->asSuccess()
                ->withTitle(lang('update_template_success'))
                ->addToBody(sprintf(lang('update_template_success_desc'), lang($template->template_name)));

            if (ee()->input->post('submit') == 'save_and_close') {
                $alert->defer();
                ee()->functions->redirect(ee('CP/URL')->make('design/system'));
            }

            $alert->now();
        }

        $author = $template->getLastAuthor();

        $vars = array(
            'form_url' => ee('CP/URL')->make('design/system/edit/' . $template->template_id),
            'template' => $template,
            'author' => (empty($author)) ? '-' : $author->getMemberName(),
        );

        $vars['buttons'] = [
            [
                'name' => 'submit',
                'type' => 'submit',
                'value' => 'save',
                'text' => 'save',
                'working' => 'btn_saving'
            ],
            [
                'name' => 'submit',
                'type' => 'submit',
                'value' => 'save_and_close',
                'text' => 'save_and_close',
                'working' => 'btn_saving'
            ]
        ];

        $this->loadCodeMirrorAssets();

        ee()->view->cp_page_title = lang($template->template_name);
        ee()->view->save_btn_text = lang('save');
        ee()->view->save_btn_text_working = 'btn_saving';

        // Supress browser XSS check that could cause obscure bug after saving
        ee()->output->set_header("X-XSS-Protection: 0");

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('design/system')->compile() => lang('system_message_templates'),
            '' => lang('edit_template_title')
        );

        ee()->cp->render('design/system/edit', $vars);
    }
}

// EOF
