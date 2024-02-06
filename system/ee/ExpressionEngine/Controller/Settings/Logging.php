<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Settings;

use CP_Controller;

/**
 * Logging Settings Controller
 */
class Logging extends Settings
{
    /**
     * General Settings
     */
    public function index($routes = null, $errors = null)
    {
        if (! ee('Permission')->can('access_logs')) {
            show_error(lang('unauthorized_access'), 403);
        }

        if (ee('Request')->isPost()) {
            $config = $this->saveLoggingSettings();
        } else {
            $config = config_item('logging') ?: ['*' => ['DatabaseHandler' => []]];
            if (!is_array($config)) {
                $config = json_decode($config, true);
            }
        }

        $base_url = ee('CP/URL')->make('settings/logging');

        $vars = array();
        $columns = array(
            'channel' => array(
                'label' => 'channel',
                'desc' => 'logging_channel_desc'
            ),
            'handler' => array(
                'label' => 'handler',
                'desc' => 'logging_handler_desc'
            ),
            'level' => array(
                'label' => 'level',
                'desc' => 'logging_level_desc'
            ),
            'processors' => array(
                'label' => 'processors',
                'desc' => 'logging_processors_desc'
            ),
        );
        $defaultLoggingSettings = ee('CP/GridInput', array(
            'field_name' => 'defaultLogging',
            'show_add_button' => true
        ));
        $defaultLoggingSettings->loadAssets();
        $defaultLoggingSettings->setColumns($columns);
        $defaultLoggingSettings->setNoResultsText('logging_not_configured', 'configure');

        $specificLoggingSettings = ee('CP/GridInput', array(
            'field_name' => 'specificLogging',
            'show_add_button' => true
        ));
        $specificLoggingSettings->loadAssets();
        $specificLoggingSettings->setColumns($columns);
        $specificLoggingSettings->setNoResultsText('logging_not_configured', 'configure');

        $defaultLoggingData = [];
        $specificLoggingData = [];
        $defaultLoggingConfig = [];
        $specificLoggingConfig = [];
        $id = 0;

        foreach ($config as $channel => $channelConfig) {
            foreach ($channelConfig as $handler => $handlerConfig) {
                $var = $channel == '*' ? 'defaultLoggingConfig' : 'specificLoggingConfig';
                array_push($$var, [
                    'id' => $id++,
                    'channel' => $channel,
                    'handler' => $handler,
                    'level' => $handlerConfig['level'] ?? 'info',
                    'processors' => $handlerConfig['processors'] ?? []
                ]);
            }
        }

        foreach ($defaultLoggingConfig as $row) {
            $defaultLoggingData[] = $this->getRow($row, $errors);
        }
        $defaultLoggingSettings->setData($defaultLoggingData);
        $blankRow = $this->getRow(['channel' => '*'], null);
        $defaultLoggingSettings->setBlankRow($blankRow['columns']);

        foreach ($specificLoggingConfig as $row) {
            $specificLoggingData[] = $this->getRow($row, $errors);
        }
        $specificLoggingSettings->setData($specificLoggingData);
        $blankRow = $this->getRow([], null);
        $specificLoggingSettings->setBlankRow($blankRow['columns']);

        $vars = array(
            'base_url' => $base_url,
            'cp_page_title' => lang('logging_settings'),
            'sections' => array(
                array(
                    array(
                        'title' => 'common_logging_settings',
                        'wide' => true,
                        'grid' => true,
                        'fields' => array(
                            'defaultLogging' => array(
                                'type' => 'html',
                                'content' => ee()->load->view('_shared/table', $defaultLoggingSettings->viewData(), true)
                            )
                        )
                    ),
                    array(
                        'title' => 'specific_logging_settings',
                        'wide' => true,
                        'grid' => true,
                        'fields' => array(
                            'specificLogging' => array(
                                'type' => 'html',
                                'content' => ee()->load->view('_shared/table', $specificLoggingSettings->viewData(), true)
                            )
                        )
                    )
                )
            )
        );

        ee()->view->cp_breadcrumbs = array(
            '' => lang('template_routes')
        );

        ee()->view->ajax_validate = true;
        ee()->view->buttons = [
            [
                'name' => 'submit',
                'type' => 'submit',
                'value' => 'save',
                'text' => 'save',
                'working' => 'btn_saving'
            ]
        ];

        ee()->cp->render('settings/form', $vars);
    }

    private function getRow($configRow, $errors)
    {
        // if the handler is NullHandler, get the next one and mark it as teminator
        // (if there is no next one for this channel, add a new one)

        $row = array();

        $id = !empty($configRow && isset($configRow['id'])) ? $configRow['id'] : 0;
        $row['attrs']['row_id'] = 'new_row_' . $id;

        $row['columns'] = array(
            // channel
            array(
                'html' => (isset($configRow['channel']) && $configRow['channel'] == '*') ? lang('all_channels') : form_input('channel', $configRow['channel'] ?? ''),
                'error' => (isset($errors) && $errors->hasErrors("routes[rows][{$id}][template_id]")) ? implode('<br>', $errors->getErrors("routes[rows][{$id}][template_id]")) : null
            ),
            // handler
            array(
                'html' => form_dropdown('handler', $this->getHandlerOptions()['handlers'], $configRow['handler'] ?? 'DatabaseHandler'),
                'error' => (isset($errors) && $errors->hasErrors("routes[rows][{$id}][route]")) ? implode('<br>', $errors->getErrors("routes[rows][{$id}][route]")) : null
            ),
            // level
            array(
                'html' => form_dropdown('level', $this->getLevelOptions(), $configRow['level'] ?? 'info'),
                'error' => (isset($errors) && $errors->hasErrors("routes[rows][{$id}][route]")) ? implode('<br>', $errors->getErrors("routes[rows][{$id}][route]")) : null
            ),
            // processors
            array(
                'html' => ee('View')->make('_shared/form/field')
                    ->render(array(
                        'field_name' => "processors",
                        'field' => array(
                            'type' => 'checkbox',
                            'choices' => $this->getHandlerOptions()['processors'],
                            'value' => $configRow['processors'] ?? [],
                            'too_many' => 100
                        ),
                        'grid' => true,
                        'errors' => $errors
                    )
                ),
                'error' => (isset($errors) && $errors->hasErrors("routes[rows][{$id}][route]")) ? implode('<br>', $errors->getErrors("routes[rows][{$id}][route]")) : null
            ),

        );
        $row['attrs']['class'] = 'setting-field';

        return $row;
    }

    private function getHandlerOptions()
    {
        static $options;

        if (!empty($options)) {
            return $options;
        }

        $config = ee()->config->loadFile('logger');
        $handlers = array_keys($config['handlers']);
        $processors = array_keys($config['processors']);
        $options = array(
            'handlers' => array_combine($handlers, $handlers),
            'processors' => array_combine($processors, $processors)
        );
        return $options;
    }

    private function getLevelOptions()
    {
        static $levels;

        if (!empty($levels)) {
            return $levels;
        }

        $levels = array(
            'debug' => 'debug',
            'info' => 'info',
            'notice' => 'notice',
            'warning' => 'warning',
            'error' => 'error',
            'critical' => 'critical',
            'alert' => 'alert',
            'emergency' => 'emergency'
        );
        return $levels;
    }

    private function saveLoggingSettings()
    {
        $post = ee('Security/XSS')->clean($_POST);

        $defaultLogging = $post['defaultLogging']['rows'] ?? [];
        $specificLogging = $post['specificLogging']['rows'] ?? [];

        $config = [];
        foreach ($defaultLogging as $row) {
            $config['*'][$row['handler']] = [
                'level' => $row['level'],
                'processors' => $row['processors']
            ];
        }
        foreach ($specificLogging as $row) {
            $config[$row['channel']][$row['handler']] = [
                'level' => $row['level'],
                'processors' => $row['processors']
            ];
        }

        ee()->config->update_site_prefs(['logging' => $config]);

        ee('CP/Alert')->makeInline('shared-form')
            ->asSuccess()
            ->withTitle(lang('preferences_updated'))
            ->addToBody(lang('preferences_updated_desc'))
            ->now();

        return $config;
    }
}
// END CLASS

// EOF
