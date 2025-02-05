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
use ExpressionEngine\Service\TemplateGenerator\Exceptions\ValidationException;

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
            'buttons' => array(
                array(
                    'name' => '',
                    'type' => 'submit',
                    'value' => 'save',
                    'shortcut' => 's',
                    'text' => lang('generate'),
                    'working' => 'generate_templates_started'
                )
            )
        );

        if (!empty($_POST)) {
            $result = $this->create();
            if ($result !== true) {
                $vars['errors'] = $result;
            }
        }

        $generatorsList = ee('TemplateGenerator')->registerAllTemplateGenerators();
        $generators = [];
        $generatorsToggle = [];
        $options = [];

        foreach ($generatorsList as $key => $generator) {
            $generators[$key] = $generator->getName();
            $generatorsToggle[$key] = $key;
            $flashData = ee()->session->flashdata($key) ?: [];

            foreach ($generator->getOptions() as $optionKey => $optionParams) {
                if ($optionKey == 'site_id') {
                    continue; //always current site
                }
                if ($optionKey == 'template_engine' && count($optionParams['choices']) == 1) {
                    continue;
                }
                if ($optionKey == 'templates') {
                    unset($optionParams['choices']['all']);
                    $optionParams['value'] = array_keys($optionParams['choices']);
                }

                if (isset($flashData[$optionKey])) {
                    $optionParams['value'] = $flashData[$optionKey];
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

    protected function create()
    {
        try {
            $generatorName = ee('Request')->post('generator');
            $generator = ee('TemplateGenerator')->make($generatorName);
        } catch (\Exception $e) {
            if (ee('Request')->isAjax()) {
                ee()->output->send_ajax_response(array(
                    'error' => $e->getMessage()
                ));
            } else {
                show_error($e->getMessage());
            }
        }

        $options = array_merge(
            ['site_id' => ee()->config->item('site_id')],
            ee('Request')->post($generatorName)
        );
        $options['template_group'] = ee('Security/XSS')->clean($options['template_group']);

        // $validationResult = $generator->validate($options);

        // Single ajax field validation
        if (ee('Request')->isAjax() && ($field = ee()->input->post('ee_fv_field'))) {
            $validationResult = $generator->validate($options);
            $_POST['ee_fv_field'] = str_replace(['[', ']', $generatorName], '', $field);
            if ($response = $this->ajaxValidation($validationResult)) {
                return ee()->output->send_ajax_response($response);
            }
        }

        try {
            $result = $generator->generate($options);
        } catch (ValidationException $e) {
            // Full validation
            foreach ($e->getResult()->getFailedRules() as $field => $rules) {
                $field = $generatorName . '[' . $field . ']';
                foreach ($rules as $rule) {
                    $e->getResult()->addFailed($field, $rule);
                }
            }
            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(lang('generate_templates_error'))
                ->addToBody(lang('create_template_group_error_desc'))
                ->now();

            return $e->getResult();
        } catch (\Exception $e) {
            throw $e;
            // note: if the exception was triggered in embed, we might still get part of template
            // because embed is echo'ing stuff instead of returning
            show_error($e->getMessage());
        }

        ee('CP/Alert')->makeInline('shared-form')
            ->asSuccess()
            ->withTitle(lang('generate_templates_created_successfully'))
            ->addToBody(sprintf(lang('create_template_group_success_desc'), $result['group']->group_name))
            ->defer();

        ee()->functions->redirect(ee('CP/URL')->make('design/manager/' . $result['group']->group_name));

        return true;
    }
}

// EOF
