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
                                'n' => 'portage_channels'
                            ]
                        ],
                    )
                ),
                array(
                    'title' => 'portage_channels',
                    'desc' => 'portage_channels_desc',
                    'group' => 'portage_channels',
                    'fields' => array(
                        'portage_channels' => array(
                            'type' => 'checkbox',
                            'choices' => ee('Model')->get('Channel')->filter('site_id', ee()->config->item('site_id'))->all()->getDictionary('channel_id', 'channel_title')
                        )
                    )
                )
            )
        );

        if (ee('Request')->isPost()) {

            $elements = [];
            $fileName = 'portage-' . ee()->config->item('site_short_name');
            /*if (ee('Request')->post('export_full_portage') != 'n') {
                $channels = ee('Model')
                    ->get('Channel')
                    ->filter('site_id', ee()->config->item('site_id'))
                    ->all();
            } else {
                $channels = ee('Model')
                    ->get('Channel')
                    ->filter('site_id', ee()->config->item('site_id'))
                    ->filter('channel_id', 'IN', ee('Request')->post('portage_channels'))
                    ->all();
                $fileName .= '-channels' . implode('_', ee('Request')->post('portage_channels'));
            }*/

            if (ee('Request')->post('export_zip') != 'y') {
                ee('Portage')->exportDir();

                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('portage_exported'))
                    ->addToBody(lang('portage_exported_to_folder'))
                    ->defer();

                ee()->functions->redirect(ee('CP/URL', 'utilities/portage/export'));
            }

            if (empty($channels)) {
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('channel_set_not_exported'))
                    ->addToBody(lang('channel_set_not_exported_desc'))
                    ->defer();

                ee()->functions->redirect(ee('CP/URL', 'utilities/portage/export'));
            }

            $file = ee('Portage')->export($channels);

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
                    PATH_CACHE . 'portage/',
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
            $set_file = ee('Request')->file('set_file');

            $validator = ee('Validation')->make(array(
                'set_file' => 'required',
            ));

            $result = $validator->validate(array('set_file' => $set_file['name']));

            if ($result->isNotValid()) {
                $errors = $result;
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('channel_set_upload_error'))
                    ->addToBody(lang('channel_set_upload_error_desc'))
                    ->now();

                $vars['errors'] = $errors;
            } elseif (strtolower(pathinfo($set_file['name'], PATHINFO_EXTENSION)) !== 'zip') {
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('channel_set_filetype_error'))
                    ->addToBody(lang('channel_set_filetype_error_desc'))
                    ->now();
            } else {
                $set = ee('Portage')->importUpload($set_file);
                $path = ee('Encrypt')->encode(
                    $set->getPath(),
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
                ->withTitle(lang('channel_set_upload_error'))
                ->addToBody(lang('channel_set_upload_error_desc'))
                ->defer();

            ee()->functions->redirect(ee('CP/URL', 'channels/sets'));
        }

        // load up the set
        $set = ee('Portage')->importDir($path);

        // posted values? grab 'em
        if (isset($_POST)) {
            $set->setAliases($_POST);
        }

        $result = $set->validate();

        if ($result->isValid()) {
            $set->save();
            $set->cleanUpSourceFiles();

            ee()->session->set_flashdata(
                'imported_channels',
                $set->getIdsForElementType('channels')
            );

            ee()->session->set_flashdata(
                'imported_category_groups',
                $set->getIdsForElementType('category_groups')
            );

            ee()->session->set_flashdata(
                'imported_field_groups',
                $set->getIdsForElementType('field_groups')
            );

            $alert = ee('CP/Alert')->makeInline('shared-form')
                ->asSuccess()
                ->withTitle(lang('channel_set_imported'))
                ->addToBody(lang('channel_set_imported_desc'))
                ->defer();

            ee()->functions->redirect(ee('CP/URL', 'channels'));
        }

        if ($result->isRecoverable()) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(lang('channel_set_duplicates_error'))
                ->addToBody(lang('channel_set_duplicates_error_desc'))
                ->now();
        } else {
            $set->cleanUpSourceFiles();
            $errors = $result->getErrors();
            $model_errors = $result->getModelErrors();
            foreach (array('Channel Field', 'Category', 'Category Group', 'Status') as $type) {
                if (isset($model_errors[$type])) {
                    foreach ($model_errors[$type] as $model_error) {
                        list($model, $field, $rule) = $model_error;
                        foreach ($rule as $error) {
                            list($key, $params) = $error->getLanguageData();
                            $errors[] = $type . ': ' . lang($field) . ' &mdash; ' . vsprintf(lang($key), (array) $params);
                        }
                    }
                }
            }

            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(lang('channel_set_upload_error'))
                ->addToBody($errors)
                ->defer();

            ee()->functions->redirect(ee('CP/URL', 'channels/sets'));
        }

        $vars = $this->createAliasForm($set, $result);

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('channels')->compile() => lang('channels')
        );

        ee()->view->cp_page_title = lang('import_channel');
        ee()->cp->render('settings/form', $vars);
    }

    private function createAliasForm($set, $result)
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

        foreach ($result->getRecoverableErrors() as $section => $errors) {
            foreach ($errors as $error) {
                $fields = array();

                list($model, $field, $ident, $rule) = $error;

                $model_name = $model->getName();
                $long_field = $result->getLongFieldIfShortened($model, $field);

                // Show the current model title in the section header
                $title_field = $result->getTitleFieldFor($model);
                $title = ee('Format')->make('Text', $model->$title_field)->convertToEntities();

                // Frequently the error is on the short_name, but in those cases
                // you really want to edit the long name as well, so we'll show it.
                if (isset($long_field)) {
                    $key = $model_name . '[' . $ident . '][' . $long_field . ']';
                    $encoded_key = ee('Format')->make('Text', $key)->convertToEntities()->compile();
                    if (isset($hidden[$key])) {
                        $vars['sections'][$section . ': ' . $title][] = array(
                            'title' => $long_field,
                            'fields' => array(
                                $encoded_key => array(
                                    'type' => 'text',
                                    'value' => $model->$long_field,
                                    // 'required' => TRUE
                                )
                            )
                        );
                        unset($hidden[$key]);
                    }
                }

                $key = $model_name . '[' . $ident . '][' . $field . ']';
                $encoded_key = ee('Format')->make('Text', $key)->convertToEntities()->compile();
                $vars['sections'][$section . ': ' . $title][] = array(
                    'title' => $field,
                    'fields' => array(
                        $encoded_key => array(
                            'type' => 'text',
                            'value' => $model->$field,
                            'required' => true
                        )
                    )
                );

                unset($hidden[$key]);

                foreach ($rule as $r) {
                    $vars['errors']->addFailed($model_name . '[' . $ident . '][' . $field . ']', $r);
                }
            }
        }

        if (! empty($hidden)) {
            $vars['form_hidden'] = $hidden;
        }

        $path = ee('Encrypt')->encode($set->getPath(), ee()->config->item('session_crypt_key'));

        // Final view variables we need to render the form
        $vars += array(
            'base_url' => ee('CP/URL')->make('channels/sets/doImport', ['path' => $path]),
            'save_btn_text' => 'btn_save_settings',
            'save_btn_text_working' => 'btn_saving',
        );

        return $vars;
    }
}
// END CLASS

// EOF
