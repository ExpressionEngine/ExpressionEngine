<?php

/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
*/

namespace ExpressionEngine\Addons\Pro\Controller\Settings;

use ExpressionEngine\Controller\Settings;

/**
 * Frontedit Settings Controller
 */
class Frontedit extends Settings\Pro
{
    public function __construct()
    {
        $this->base_url = ee('CP/URL')->make('settings/pro/frontedit');
    }

    /**
     * General Settings Page
     */
    public function frontedit($segments)
    {
        $fields = [
            'enable_dock',
            'enable_frontedit',
            'automatic_frontedit_links',
        ];

        if (ee('Request')->isPost()) {
            $data = array();
            foreach ($fields as $field) {
                $data[$field] = ee('Security/XSS')->clean(ee('Request')->post($field));
            }

            $config_update = ee()->config->update_site_prefs($data);

            if (!empty($config_update)) {
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('cp_message_issue'))
                    ->addToBody($config_update)
                    ->defer();
            } else {
                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('success'))
                    ->addToBody(lang('pro_settings_updated'))
                    ->defer();
            }

            ee()->functions->redirect($this->base_url);
        }

        $settings = [];
        foreach ($fields as $field) {
            $settings[$field] = ee()->config->item($field) === false ? 'y' : ee()->config->item($field);
        }

        $vars = [
            'sections' => [
                [
                    [
                        'title' => 'enable_dock',
                        'desc' => 'enable_dock_desc',
                        'fields' => [
                            'enable_dock' => [
                                'type' => 'yes_no',
                                'value' => $settings['enable_dock'],
                                'group_toggle' => [
                                    'y' => 'enable_dock'
                                ]
                            ]
                        ]
                    ],
                    [
                        'title' => 'enable_frontedit',
                        'desc' => 'enable_frontedit_desc',
                        'group' => 'enable_dock',
                        'fields' => [
                            'enable_frontedit' => [
                                'type' => 'yes_no',
                                'value' => $settings['enable_frontedit'],
                                'group_toggle' => [
                                    'y' => 'enable_frontedit'
                                ]
                            ]
                        ]
                    ],
                    [
                        'title' => 'automatic_frontedit_links',
                        'desc' => 'automatic_frontedit_links_desc',
                        'group' => 'enable_dock|enable_frontedit',
                        'fields' => [
                            'automatic_frontedit_links' => [
                                'type' => 'yes_no',
                                'value' => $settings['automatic_frontedit_links']
                            ]
                        ]
                    ],
                ]
            ]
        ];

        $vars += array(
            'base_url' => ee('CP/URL')->make('settings/pro/frontedit'),
            'cp_page_title' => lang('frontedit_settings'),
            'save_btn_text' => 'btn_save_settings',
            'save_btn_text_working' => 'btn_saving'
        );

        ee()->view->cp_breadcrumbs = array(
            '' => lang('frontedit')
        );

        ee()->cp->render('settings/form', $vars);
    }
}

// EOF
