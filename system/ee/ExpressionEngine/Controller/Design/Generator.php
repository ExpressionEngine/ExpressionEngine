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
use ExpressionEngine\Model\Template\TemplateGroup as TemplateGroupModel;
use ExpressionEngine\Service\Validation\Result as ValidationResult;

/**
 * Template Generator Controller
 */
class Generator extends AbstractDesignController
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        if (! ee('Permission')->hasAll('can_access_design', 'can_create_template_groups')) {
            show_error(lang('unauthorized_access'), 403);
        }

        $this->stdHeader();
    }

    public function index()
    {
        $vars = array(
            'ajax_validate' => true,
            'base_url' => ee('CP/URL')->make('design/generator'),
            'save_btn_text' => sprintf(lang('generate')),
            'save_btn_text_working' => 'generate_templates_started'
        );

        ee('TemplateGenerator')->site_id = ee()->config->item('site_id');
        if (! empty($_POST)) {
            try {
                ee('TemplateGenerator')->setGenerator(ee('Request')->post('generator'));
            } catch (\Exception $e) {
                if (ee('Request')->isAjax()) {
                    ee()->output->send_ajax_response(array(
                        'error' => $e->getMessage()
                    ));
                } else {
                    show_error($e->getMessage());
                }
            }

            //remap the submitted POST fields to actual template generator fields
            $post = $_POST[ee('Request')->post('generator')];
            ee('TemplateGenerator')->setOptionValues($post);

            // set up validator for the options
            $validator = ee('TemplateGenerator')->getValidator();
            $validationResult = $validator->validate($post);

            if (ee('Request')->isAjax() && ($field = ee()->input->post('ee_fv_field'))) {
                $_POST['ee_fv_field'] = str_replace(['[', ']', ee('Request')->post('generator')], '', $field);
                if ($response = $this->ajaxValidation($validationResult)) {
                    return ee()->output->send_ajax_response($response);
                }
            }

            if ($validationResult->isValid()) {
                $templates = ee('TemplateGenerator')->getGenerator()->getInstance()->getTemplates();
                if (isset($post['templates']) && !empty($post['templates'])) {
                    $templates = array_filter($templates, function ($key) use ($post) {
                        return in_array($key, $post['templates']);
                    }, ARRAY_FILTER_USE_KEY);
                }
                if (empty($templates)) {
                    show_error('generate_templates_no_templates');
                }
                // we'll start with index templates
                if (isset($templates['index'])) {
                    $indexTmpl = $templates['index'];
                    unset($templates['index']);
                    $templates = array_merge(['index' => $indexTmpl], $templates); // we want index to be created first
                }

                try {
                    $group = ee('TemplateGenerator')->createTemplateGroup(ee('Security/XSS')->clean($post['template_group']));

                    foreach ($templates as $template => $templateDescription) {
                        $templateData = ee('TemplateGenerator')->generate($template);
                        // now we need to save the template
                        $templateInfo = [
                            'template_data' => $templateData,
                            'template_notes' => $templateDescription
                        ];
                        ee('TemplateGenerator')->createTemplate($group, $template, $templateInfo);
                    }
                } catch (\Exception $e) {
                    // note: if the exception was triggered in embed, we might still get part of template
                    // because embed is echo'ing stuff instead of returning
                    show_error($e->getMessage());
                }

                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('generate_templates_created_successfully'))
                    ->addToBody(sprintf(lang('create_template_group_success_desc'), $group->group_name))
                    ->defer();

                ee()->functions->redirect(ee('CP/URL')->make('design/manager/' . $group->group_name));
            } else {
                foreach ($validationResult->getFailedRules() as $field => $rules) {
                    $field = ee('Request')->post('generator') . '[' . $field . ']';
                    foreach ($rules as $rule) {
                        $validationResult->addFailed($field, $rule);
                    }
                }
                $vars['errors'] = $validationResult;
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('generate_templates_error'))
                    ->addToBody(lang('create_template_group_error_desc'))
                    ->now();
            }
        }

        $generatorsList = ee('TemplateGenerator')->registerAllTemplateGenerators();
        $generators = [];
        $generatorsToggle = [];
        $options = [];
        foreach ($generatorsList as $key => $info) {
            ee('TemplateGenerator')->setGenerator($key);
            $generators[$key] = ee('TemplateGenerator')->getGenerator()->getInstance()->getName();
            $generatorsToggle[$key] = $key;
            $generatorOptions = ee('TemplateGenerator')->getOptions();
            foreach ($generatorOptions as $optionKey => $optionParams) {
                if ($optionKey == 'site_id') {
                    continue; //always current site
                }
                if (isset($optionParams['callback']) && !empty($optionParams['callback'])) {
                    $optionParams['choices'] = ee('TemplateGenerator')->populateOptionCallback($optionParams['callback']);
                }
                if ($optionKey == 'template_engine' && count($optionParams['choices']) == 1) {
                    continue;
                }
                if ($optionKey == 'templates') {
                    unset($optionParams['choices']['all']);
                    $optionParams['value'] = array_keys($optionParams['choices']);
                }

                $options[] = [
                    'title' => lang($optionKey),
                    'desc' => isset($optionParams['desc']) ? lang($optionParams['desc']) : '',
                    'group' => $key,
                    'fields' => [
                        $key . '[' . $optionKey . ']' => $optionParams
                    ]
                ];
            }
        }

        $sections = [
            [
                [
                    'title' => 'template_generator',
                    'desc' => '',
                    'fields' => array(
                        'generator' => array(
                            'type' => 'select',
                            'choices' => $generators,
                            'group_toggle' => $generatorsToggle,
                            'value' => 'channel:entries'
                        )
                    )
                ]
            ],
            $options
        ];

        $vars['sections'] = $sections;

        $this->generateSidebar();
        ee()->view->cp_page_title = lang('template_generator');

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('design')->compile() => lang('templates'),
            '' => lang('template_generator')
        );

        ee()->cp->render('settings/form', $vars);
    }


    /**
     * Sets a template group entity with the POSTed data and validates it, setting
     * an alert if there are any errors.
     *
     * @param TemplateGroupModel $$group A TemplateGroup entity
     * @return mixed FALSE if nothing was posted, void if it was an AJAX call,
     *  or a ValidationResult object.
     */
    private function validateTemplateGroup(TemplateGroupModel $group)
    {
        if (empty($_POST)) {
            return false;
        }

        $group->group_name = ee()->input->post('group_name');
        $group->is_site_default = ee()->input->post('is_site_default');

        $result = $group->validate();

        $field = ee()->input->post('ee_fv_field');

        if ($response = $this->ajaxValidation($result)) {
            ee()->output->send_ajax_response($response);
        }

        if ($result->failed()) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(lang('edit_template_group_error'))
                ->addToBody(lang('edit_template_group_error_desc'))
                ->now();
        }

        return $result;
    }
}

// EOF
