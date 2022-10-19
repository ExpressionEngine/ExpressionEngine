<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Utilities;

use ExpressionEngine\Service\Portage\PortageExport;

/**
 * Portage Controller
 */
class Portage extends Utilities
{
    public function __construct()
    {
        parent::__construct();

        if (! ee('Permission')->isSuperAdmin()) {
            show_error(lang('unauthorized_access'), 403);
        }

        ee()->lang->load('admin_content');
    }
    
    public function index()
    {
        show_error(lang('unauthorized_access'), 403);
    }
    
    public function export()
    {
        if (AJAX_REQUEST) {
            ee()->output->send_ajax_response(['success' => 'success']);
        }

        if (! ee('Permission')->can('access_data')) {
            show_error(lang('unauthorized_access'), 403);
        }

        ee('CP/Alert')->makeInline('portage_explained')
            ->asTip()
            ->cannotClose()
            ->addToBody(sprintf(
                lang('portage_explained_desc'),
                DOC_URL . 'control-panel/file-manager/file-manager.html#compatibility-mode',
                ee('CP/URL')->make('utilities/db-backup')->compile(),
                ee('CP/URL')->make('settings/content-design')->compile() . '#fieldset-file_manager_compatibility_mode')
            )
            ->now();

        $portageExport = new PortageExport();
        $portageChoices = [
            'add-ons' => lang('addons')
        ];
        $portableModels = $portageExport->getPortableModels();
        foreach ($portableModels as $portableModel) {
            $portageChoices[$portableModel['name']] = array_reverse(explode(':', $portableModel['name']))[0];
        }

        $vars['hide_top_buttons'] = true;
        $vars['sections'] = array(
            array(
                array(
                    'title' => 'export_portage_as_zip',
                    'desc' => 'export_portage_as_zip_desc',
                    'fields' => array(
                        'export_zip' => [
                            'type' => 'yes_no',
                            'value' => 'y'
                        ],
                    )
                ),
                array(
                    'title' => 'export_full_portage',
                    'desc' => 'export_full_portage_desc',
                    'fields' => array(
                        'export_full_portage' => [
                            'type' => 'yes_no',
                            'value' => 'y',
                            'group_toggle' => [
                                'n' => 'portage_elements'
                            ]
                        ],
                    )
                ),
                array(
                    'title' => 'portage_elements',
                    'desc' => 'portage_elements_desc',
                    'group' => 'portage_elements',
                    'fields' => array(
                        'portage_elements' => array(
                            'type' => 'checkbox',
                            'choices' => $portageChoices,
                            'value' => array_keys($portageChoices)
                        )
                    )
                )
            )
        );

        if (ee('Request')->isPost()) {

            $elements = [];
            $fileName = 'portage-' . ee()->config->item('site_short_name');
            $portage_elements = ee('Request')->post('export_full_portage') != 'n' ? [] : ee('Request')->post('portage_elements');

            
            if (ee('Request')->post('export_full_portage') == 'n' && empty($portage_elements)) {
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('channel_set_not_exported'))
                    ->addToBody(lang('channel_set_not_exported_desc'))
                    ->defer();

                ee()->functions->redirect(ee('CP/URL', 'utilities/portage/export'));
            }

            if (ee('Request')->post('export_zip') != 'y') {
                ee('Portage')->exportDir($portage_elements);

                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('portage_exported'))
                    ->addToBody(lang('portage_exported_to_folder'))
                    ->defer();

                ee()->functions->redirect(ee('CP/URL', 'utilities/portage/export'));
            }

            $file = ee('Portage')->export($portage_elements);

            $data = file_get_contents($file);

            ee()->load->helper('download');
            force_download($fileName . '-' . ee()->localize->now . '.zip', $data);
            exit;
        }

        ee()->view->extra_alerts = ['portage_explained'];

        ee()->view->ajax_validate = true;
        ee()->view->cp_page_title = lang('portage_export');
        ee()->view->base_url = ee('CP/URL')->make('utilities/portage/export');
        ee()->view->save_btn_text = 'btn_export';
        ee()->view->save_btn_text_working = 'btn_processing';

        ee()->view->cp_breadcrumbs = array(
            '' => lang('portage_export')
        );

        ee()->cp->render('settings/form', $vars);
    }

    /**
     * General Settings
     */
    public function import()
    {
        $base_url = ee('CP/URL', 'utilities/portage/import');

        $vars = array(
            'ajax_validate' => true,
            'base_url' => $base_url,
            'errors' => null,
            'has_file_input' => true,
            'save_btn_text' => 'btn_import',
            'save_btn_text_working' => 'btn_processing',
            'sections' => array(
                array(
                    array(
                        'title' => 'import_zip_portage',
                        'desc' => 'import_zip_portage_desc',
                        'fields' => array(
                            'import_zip' => [
                                'type' => 'yes_no',
                                'value' => 'y',
                                'group_toggle' => [
                                    'y' => 'import_zip'
                                ]
                            ],
                        )
                    ),
                    array(
                        'title' => 'portage_file',
                        'desc' => 'portage_file_desc',
                        'group' => 'import_zip',
                        'fields' => array(
                            'set_file' => array(
                                'type' => 'file',
                                'required' => true
                            )
                        )
                    ),
                )
            )
        );

        if (ee('Request')->isPost()) {
            if (AJAX_REQUEST) {
                ee()->output->send_ajax_response(['success' => 'success']);
            }
            if (ee('Request')->post('import_zip') != 'y') {
                $path = ee('Encrypt')->encode(
                    PATH_CACHE . 'portage/import/',
                    ee()->config->item('session_crypt_key')
                );
                ee()->functions->redirect(
                    ee('CP/URL')->make(
                        'utilities/portage/doImport',
                        ['path' => $path]
                    )
                );
            }
        }
        if (! empty($_FILES)) {
            $portage_file = ee('Request')->file('set_file');

            $validator = ee('Validation')->make(array(
                'set_file' => 'required',
            ));

            $result = $validator->validate(array('set_file' => $portage_file['name']));

            if ($result->isNotValid()) {
                $errors = $result;
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('portage_import_error'))
                    ->addToBody(lang('portage_import_error_desc'))
                    ->now();

                $vars['errors'] = $errors;
            } elseif (strtolower(pathinfo($portage_file['name'], PATHINFO_EXTENSION)) !== 'zip') {
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('channel_set_filetype_error'))
                    ->addToBody(lang('channel_set_filetype_error_desc'))
                    ->now();
            } else {
                $portage = ee('Portage')->importUpload($portage_file);
                $path = ee('Encrypt')->encode(
                    $portage->getPath(),
                    ee()->config->item('session_crypt_key')
                );
                ee()->functions->redirect(
                    ee('CP/URL')->make(
                        'utilities/portage/doImport',
                        ['path' => $path]
                    )
                );
            }
        }

        ee()->view->cp_breadcrumbs = array(
            '' => lang('portage')
        );

        ee()->view->cp_page_title = lang('portage_import');
        ee()->cp->render('settings/form', $vars);
    }

    /**
     * Import a channel set
     */
    public function doImport()
    {
        ee()->lang->load('form_validation');
        $path = ee('Request')->get('path');
        $path = ee('Encrypt')->decode(
            $path,
            ee()->config->item('session_crypt_key')
        );

        // no path or unacceptable path? abort!
        if (! $path || strpos($path, '..') !== false || ! file_exists($path)) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(lang('portage_import_error'))
                ->addToBody(lang('portage_path_not_valid'))
                ->defer();

            ee()->functions->redirect(ee('CP/URL', 'utilities/portage/import'));
        }

        // load up the set
        $portage = ee('Portage')->importDir($path);

        // posted values? grab 'em
        if (isset($_POST)) {
            $portage->setAliases($_POST);
        }

        $result = $portage->validate();

        if ($result->isValid()) {
            $portage->save();
            $portage->cleanUpSourceFiles();

            $alert = ee('CP/Alert')->makeInline('shared-form')
                ->asSuccess()
                ->withTitle(lang('portage_imported'))
                ->addToBody(lang('portage_imported_desc'))
                ->defer();

            ee()->functions->redirect(ee('CP/URL', 'utilities/portage/import'));
        }

        if ($result->isRecoverable()) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(lang('portage_duplicates_error'))
                ->addToBody(lang('portage_duplicates_error_desc'))
                ->now();
        } else {
            $portage->cleanUpSourceFiles();
            $errors = $result->getErrors();
            $allModelErrors = $result->getModelErrors();
            foreach ($allModelErrors as $uuid => $model_errors) {
                foreach ($model_errors as $model_error) {
                    list($model, $field, $rule) = $model_error;
                    $title_field = $result->getTitleFieldFor($model);
                    $title = !empty($title_field) ? $model->$title_field : $model->getId();
                    foreach ($rule as $error) {
                        list($key, $params) = $error->getLanguageData();
                        $errors[] = '<b>' . array_reverse(explode(':', $model->getName()))[0] . ':</b> <code>' . $title . '</code>: <code>' . lang($field) . '</code> &mdash; ' . vsprintf(lang($key), (array) $params);
                    }
                }
            }

            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(lang('portage_import_error'))
                ->addToBody($errors)
                ->defer();

            ee()->functions->redirect(ee('CP/URL', 'utilities/portage/import'));
        }

        $vars = $this->createAliasForm($portage, $result);

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('channels')->compile() => lang('channels')
        );

        ee()->view->cp_page_title = lang('portage_import');
        ee()->cp->render('settings/form', $vars);
    }

    private function createAliasForm($portage, $result)
    {
        ee()->lang->loadfile('filemanager');
        $vars = array();
        $vars['sections'] = array();
        $vars['errors'] = new \ExpressionEngine\Service\Validation\Result();

        $hidden = array();
        foreach ($_POST as $model => $ident) {
            foreach ($ident as $field => $properties) {
                foreach ($properties as $property => $value) {
                    // Not sure what was submitted here.
                    if (is_array($value)) {
                        continue;
                    }

                    $key = "{$model}[{$field}][{$property}]";
                    $hidden[$key] = $value;
                }
            }
        }

        $sections = [];
        foreach ($result->getRecoverableErrors() as $uuid => $errors) {
            $modelErrors = new \ExpressionEngine\Service\Validation\Result();
            foreach ($errors as $error) {

                list($model, $field, $rules) = $error;
                $section = $model->getName();

                $model_name = $model->getName();
                $long_field = $result->getLongFieldIfShortened($model, $field);

                // Show the current model title in the section header
                $title_field = $result->getTitleFieldFor($model);
                $title = !empty($title_field) ? $model->$title_field : $model->getId();

                if (!isset($sections[$uuid])) {
                    $sections[$uuid] = [
                        'model' => $section,
                        'fields' => [],
                        'modelErrors' => $modelErrors,
                        'title' => $title,
                        'baseKey' => $model_name . '[' . $uuid . ']'
                    ];
                }

                // Frequently the error is on the short_name, but in those cases
                // you really want to edit the long name as well, so we'll show it.
                if (isset($long_field)) {
                    $key = $model_name . '[' . $uuid . '][' . $long_field . ']';
                    if (isset($hidden[$key])) {
                        $sections[$uuid]['fields'][] = array(
                            'title' => $long_field,
                            'fields' => array(
                                $key => array(
                                    'type' => 'text',
                                    'value' => $model->$long_field,
                                    // 'required' => TRUE
                                )
                            )
                        );
                        unset($hidden[$key]);
                    }
                }

                $key = $model_name . '[' . $uuid . '][' . $field . ']';
                $sections[$uuid]['fields'][] = array(
                    'title' => $field,
                    'fields' => array(
                        $key => array(
                            'type' => 'text',
                            'value' => $model->$field,
                            'required' => true
                        )
                    )
                );
                unset($hidden[$key]);

                foreach ($rules as $rule) {
                    // add the error to display
                    $sections[$uuid]['modelErrors']->addFailed($model_name . '[' . $uuid . '][' . $field . ']', $rule);
                    // check if this is duplicate error
                    $uuidField = method_exists($model, 'getColumnPrefix') ? $model->getColumnPrefix() . 'uuid' : 'uuid';
                    $sections[$uuid]['forbid_overwrite'] = false;
                    if ($rule->getName() == 'validateUnique' || ($rule->getName() == 'callback' && is_array($callback = $rule->getCallback()) && $callback[1] == 'validateUnique')) {
                        $sections[$uuid]['duplicate'] = true;
                        // grab the conflicting model UUID
                        $conflictingModel = ee('Model')->get($model_name)->fields($uuidField)->filter($field, $model->$field)->first();
                        $sections[$uuid]['duplicates'] = $conflictingModel->getId();
                        // if there is duplicate confict, we also need to check whether UUID not already exists
                        // if it does, overwriting duplicate should not be allowed
                        $sameUuidModel = ee('Model')->get($model_name)->fields($uuidField)->filter($uuidField, $uuid)->first();
                        if (!is_null($sameUuidModel) && $conflictingModel->getId() != $sameUuidModel->getId()) {
                            $sections[$uuid]['forbid_overwrite'] = true;
                        }
                    } else {
                        $sections[$uuid]['duplicates'] = false;
                    }
                }
            }
        }

        foreach ($sections as $uuid => $section) {
            if (!empty($section['fields'])) {
                $vars['sections'][$uuid] = array(
                    'label' => array_reverse(explode(':', $section['model']))[0],
                    'group' => $section['model'],
                    'settings' => [
                        ee('View')->make('portage/conflict')->render([
                            'baseKey' => $section['baseKey'],
                            'name' => $section['title'],
                            'fields' => $section['fields'],
                            'duplicates' => $section['duplicates'],
                            'forbid_overwrite' => $section['forbid_overwrite'],
                            'errors' => $section['modelErrors']
                        ])
                    ]
                );
            }
        }

        if (! empty($hidden)) {
            $vars['form_hidden'] = $hidden;
        }

        ee()->javascript->set_global(array(
            'lang.portage_will_overwrite' => lang('portage_will_overwrite'),
            'lang.portage_will_skip' => lang('portage_will_skip')
        ));

        $path = ee('Encrypt')->encode($portage->getPath(), ee()->config->item('session_crypt_key'));

        // Final view variables we need to render the form
        $vars += array(
            'base_url' => ee('CP/URL')->make('utilities/portage/doImport', ['path' => $path]),
            'save_btn_text' => 'btn_import',
            'save_btn_text_working' => 'btn_processing',
        );

        return $vars;
    }
}
// END CLASS

// EOF
