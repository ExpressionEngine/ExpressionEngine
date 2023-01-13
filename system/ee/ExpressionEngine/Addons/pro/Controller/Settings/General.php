<?php

/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
*/

namespace ExpressionEngine\Addons\Pro\Controller\Settings;

use ExpressionEngine\Controller\Settings;

/**
 * General Controller
 */
class General extends Settings\Pro
{
    public function __construct()
    {
        ee()->view->header = array(
            'title' => lang('general_pro_settings'),
        );
        $this->base_url = ee('CP/URL')->make('settings/pro/general');
    }

    /**
     * General Settings Page
     */
    public function general($segments)
    {
        $fields = [
            'enable_dock',
            'enable_frontedit',
            'automatic_frontedit_links',
            'enable_entry_cloning',
            'enable_mfa',
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

            ee()->functions->redirect(ee('CP/URL')->make('settings/pro/general'));
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
                    [
                        'title' => 'enable_entry_cloning',
                        'desc' => 'enable_entry_cloning_desc',
                        'fields' => [
                            'enable_entry_cloning' => [
                                'type' => 'yes_no',
                                'value' => $settings['enable_entry_cloning']
                            ]
                        ]
                    ],
                    [
                        'title' => 'enable_mfa',
                        'desc' => 'enable_mfa_desc',
                        'fields' => [
                            'enable_mfa' => [
                                'type' => 'yes_no',
                                'disabled' => version_compare(PHP_VERSION, 7.1, '<'),
                                'value' => version_compare(PHP_VERSION, 7.1, '<') ? 'n' : $settings['enable_mfa']
                            ]
                        ]
                    ],
                ]
            ]
        ];

        $vars += array(
            'base_url' => ee('CP/URL')->make('settings/pro/general'),
            'cp_page_title' => lang('settings'),
            'save_btn_text' => 'btn_save_settings',
            'save_btn_text_working' => 'btn_saving'
        );

        ee()->view->cp_breadcrumbs = array(
            '' => lang('pro_settings')
        );

        ee()->view->cp_page_title = lang('pro_settings');
        ee()->view->cp_heading = lang('pro_settings');

        ee()->cp->render('settings/form', $vars);
    }
}

// EOF
