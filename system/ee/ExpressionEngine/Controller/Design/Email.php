<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Design;

use ExpressionEngine\Controller\Design\AbstractDesign as AbstractDesignController;
use ExpressionEngine\Service\Validation\Result as ValidationResult;
use ExpressionEngine\Library\CP\Table;

/**
 * Design\Email Controller
 */
class Email extends AbstractDesignController
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        if (! ee('Permission')->can('access_design')) {
            show_error(lang('unauthorized_access'), 403);
        }

        $this->stdHeader();

        ee()->lang->loadfile('specialty_tmp');
    }

    public function index()
    {
        $templates = ee('Model')->get('SpecialtyTemplate')
            ->filter('site_id', ee()->config->item('site_id'))
            ->filter('template_type', 'email')
            ->all();

        $vars = array();

        $base_url = ee('CP/URL', 'design/email/');

        $table = ee('CP/Table', array('autosort' => true, 'subheadings' => true));
        $table->setColumns(
            array(
                'template' => array(
                    'encode' => false
                ),
                'manage' => array(
                    'type' => Table::COL_TOOLBAR
                ),
            )
        );

        $data = array();
        foreach ($templates as $template) {
            $edit_url = ee('CP/URL', 'design/email/edit/' . $template->template_id);
            $template_name = '<a href="' . $edit_url->compile() . '">' . lang($template->template_name) . '</a>';
            $data[$template->template_subtype][] = array(
                $template_name,
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

        $this->generateSidebar('email');
        ee()->view->cp_page_title = lang('template_manager');
        ee()->view->cp_heading = lang('email_message_templates');

        ee()->view->cp_breadcrumbs = array(
            '' => lang('email_message_templates')
        );

        ee()->cp->render('design/email/index', $vars);
    }

    public function edit($template_id)
    {
        $errors = null;

        $template = ee('Model')->get('SpecialtyTemplate', $template_id)
            ->filter('site_id', ee()->config->item('site_id'))
            ->filter('template_type', 'email')
            ->first();

        if (! $template) {
            show_error(lang('error_no_template'));
        }

        $result = $this->validateTemplate($template);

        if ($result instanceof ValidationResult) {
            $errors = $result;

            if ($result->isValid()) {
                $template->save();

                ee('CP/Alert')->makeInline('template-form')
                    ->asSuccess()
                    ->withTitle(lang('update_template_success'))
                    ->addToBody(sprintf(lang('update_template_success_desc'), lang($template->template_name)))
                    ->defer();

                if (ee()->input->post('submit') == 'finish') {
                    ee()->session->set_flashdata('template_id', $template->template_id);
                    ee()->functions->redirect(ee('CP/URL', 'design/email/'));
                }

                ee()->functions->redirect(ee('CP/URL', 'design/email/edit/' . $template->template_id));
            }
        }

        $vars = array(
            'ajax_validate' => true,
            'errors' => $errors,
            'base_url' => ee('CP/URL', 'design/email/edit/' . $template_id),
            'tabs' => array(
                'edit' => $this->renderEditPartial($template, $errors),
                'notes' => $this->renderNotesPartial($template, $errors),
                'variables' => $this->renderVariablesPartial($template, $errors),
            ),
            'buttons' => array(
                array(
                    'name' => 'submit',
                    'type' => 'submit',
                    'value' => 'update',
                    'text' => sprintf(lang('btn_save'), lang('template')),
                    'working' => 'btn_saving'
                ),
                array(
                    'name' => 'submit',
                    'type' => 'submit',
                    'value' => 'finish',
                    'text' => 'btn_update_and_finish_editing',
                    'working' => 'btn_saving'
                ),
            ),
            'sections' => array(),
        );

        $this->loadCodeMirrorAssets();
        ee()->cp->add_js_script(array('file' => 'cp/design/email/edit'));

        ee()->view->cp_page_title = sprintf(lang('edit_template'), lang($template->template_name));
        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('design/email')->compile() => lang('email_message_templates'),
            '' => lang('edit_template_title')
        );

        if (lang($template->template_name . '_desc') != $template->template_name . '_desc') {
            ee('CP/Alert')->makeInline('shared-form')
                ->asTip()
                ->addToBody(lang($template->template_name . '_desc'))
                ->now();
        }

        ee()->cp->render('settings/form', $vars);
    }

    /**
     * Renders the portion of a form that contains the elements for editing
     * a template's contents. This is especially useful for tabbed forms.
     *
     * @param TemplateModel $template A Template entity
     * @param bool|ValidationResult $errors FALSE (if nothing was submitted) or
     *   a ValidationResult object. This is needed to render any inline erorrs
     *   on the form.
     * @return string HTML
     */
    private function renderEditPartial($template, $errors)
    {
        $author = $template->getLastAuthor();

        $section = array(
            array(
                'title' => 'email_subject',
                'wide' => true,
                'fields' => array(
                    'data_title' => array(
                        'type' => 'text',
                        'value' => $template->data_title,
                    )
                )
            ),
            array(
                'title' => '',
                'desc' => sprintf(lang('last_edit'), ee()->localize->human_time($template->edit_date), (empty($author)) ? lang('author_unknown') : $author->screen_name),
                'wide' => true,
                'fields' => array(
                    'template_data' => array(
                        'type' => 'textarea',
                        'attrs' => 'class="template-edit"',
                        'value' => $template->template_data,
                    )
                )
            ),
            array(
                'title' => 'enable_template',
                'desc' => 'enable_template_desc',
                'fields' => array(
                    'enable_template' => array(
                        'type' => 'yes_no',
                        'value' => $template->enable_template
                    )
                )
            ),
        );

        return ee('View')->make('_shared/form/section')
            ->render(array('name' => null, 'settings' => $section, 'errors' => $errors));
    }

    /**
     * Renders the portion of a form that contains the elements for editing
     * a template's notes. This is especially useful for tabbed forms.
     *
     * @param TemplateModel $template A Template entity
     * @param bool|ValidationResult $errors FALSE (if nothing was submitted) or
     *   a ValidationResult object. This is needed to render any inline erorrs
     *   on the form.
     * @return string HTML
     */
    private function renderNotesPartial($template, $errors)
    {
        $section = array(
            array(
                'title' => 'template_notes',
                'desc' => 'template_notes_desc',
                'wide' => true,
                'fields' => array(
                    'template_notes' => array(
                        'type' => 'textarea',
                        'value' => $template->template_notes,
                    )
                )
            )
        );

        return ee('View')->make('_shared/form/section')
            ->render(array('name' => null, 'settings' => $section, 'errors' => $errors));
    }

    /**
     * Renders the portion of a form that contains the elements for listing
     * a template's variables. This is especially useful for tabbed forms.
     *
     * @param TemplateModel $template A Template entity
     * @param bool|ValidationResult $errors FALSE (if nothing was submitted) or
     *   a ValidationResult object. This is needed to render any inline erorrs
     *   on the form.
     * @return string HTML
     */
    private function renderVariablesPartial($template, $errors)
    {
        $html = '<ul class="arrow-list">';

        foreach ($template->getAvailableVariables() as $variable) {
            $html .= '<li><a href="">{' . $variable . '}</a></li>';
        }

        $html .= '</ul>';

        $section = array(
            array(
                'title' => 'variables',
                'desc' => 'variables_desc',
                'fields' => array(
                    'variables' => array(
                        'type' => 'html',
                        'content' => $html,
                    )
                )
            )
        );

        return ee('View')->make('_shared/form/section')
            ->render(array('name' => null, 'settings' => $section, 'errors' => $errors));
    }

    /**
     * Sets a template entity with the POSTed data and validates it, setting
     * an alert if there are any errors.
     *
     * @param TemplateModel $template A Template entity
     * @return mixed FALSE if nothing was posted, void if it was an AJAX call,
     *  or a ValidationResult object.
     */
    private function validateTemplate($template)
    {
        if (empty($_POST)) {
            return false;
        }

        $template->set($_POST);
        $template->edit_date = ee()->localize->now;
        $template->last_author_id = ee()->session->userdata('member_id');

        $result = $template->validate();

        if ($response = $this->ajaxValidation($result)) {
            ee()->output->send_ajax_response($response);
        }

        if ($result->failed()) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(lang('update_template_error'))
                ->addToBody(lang('update_template_error_desc'))
                ->now();
        }

        return $result;
    }
}

// EOF
