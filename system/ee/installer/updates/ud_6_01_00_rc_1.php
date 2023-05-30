<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_6_1_0_rc_1;

/**
 * Update
 */
class Updater
{
    public $version_suffix = '';

    /**
     * Do Update
     *
     * @return TRUE
     */
    public function do_update()
    {
        $steps = new \ProgressIterator([
            'addConsentLogColumns',
            'addCookieSettingsTable',
            'updateRte',
            'livePreviewCsrfExcempt',
            'recaptchaCsrfExcempt',
            '_addAllowPreview',
            'longerWatermarkImagePath',
            'addProTemplateSettings',
            'addDismissedProBannerToMember',
        ]);

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addProTemplateSettings()
    {
        if (!ee()->db->field_exists('enable_frontedit', 'templates')) {
            ee()->smartforge->add_column(
                'templates',
                [
                    'enable_frontedit' => [
                        'type' => 'char',
                        'constraint' => 1,
                        'default' => 'y',
                        'null' => false
                    ]
                ]
            );
        }
    }

    private function addConsentLogColumns()
    {
        if (!ee()->db->field_exists('consent_request_version_id', 'consent_audit_log')) {
            ee()->smartforge->add_column(
                'consent_audit_log',
                array(
                    'consent_request_version_id' => [
                        'type' => 'int',
                        'constraint' => 10,
                        'unsigned' => true,
                        'null' => true,
                        'default' => null
                    ],
                    'ip_address' => array(
                        'type' => 'varchar',
                        'constraint' => 45,
                        'default' => '0',
                        'null' => false
                    ),
                    'user_agent' => array(
                        'type' => 'varchar',
                        'constraint' => 120,
                        'null' => false
                    )
                )
            );
        }
    }

    private function addCookieSettingsTable()
    {
        if (! ee()->db->table_exists('cookie_settings')) {
            ee()->dbforge->add_field(
                [
                    'cookie_id' => [
                        'type' => 'int',
                        'constraint' => 10,
                        'unsigned' => true,
                        'null' => false,
                        'auto_increment' => true
                    ],
                    'cookie_provider' => [
                        'type' => 'varchar',
                        'constraint' => 50,
                        'null' => false
                    ],
                    'cookie_name' => [
                        'type' => 'varchar',
                        'constraint' => 50,
                        'null' => false
                    ],
                    'cookie_lifetime' => [
                        'type' => 'int',
                        'constraint' => 10,
                        'unsigned' => true,
                        'default' => null,
                    ],
                    'cookie_enforced_lifetime' => [
                        'type' => 'int',
                        'constraint' => 10,
                        'unsigned' => true,
                        'default' => null,
                    ],
                    'cookie_title' => [
                        'type' => 'varchar',
                        'constraint' => 200,
                        'null' => false,
                    ],
                    'cookie_description' => [
                        'type' => 'text',
                        'null' => true
                    ]
                ]
            );
            ee()->dbforge->add_key('cookie_id', true);
            ee()->smartforge->create_table('cookie_settings');
        }

        if (! ee()->db->table_exists('consent_request_version_cookies')) {
            ee()->dbforge->add_field(
                [
                    'consent_request_version_id' => [
                        'type' => 'int',
                        'constraint' => 10,
                        'null' => false,
                        'unsigned' => true
                    ],
                    'cookie_id' => [
                        'type' => 'int',
                        'constraint' => 10,
                        'null' => false,
                        'unsigned' => true
                    ]
                ]
            );

            ee()->smartforge->create_table('consent_request_version_cookies');

            ee()->db->data_cache = []; // Reset the cache so it will re-fetch a list of tables
            ee()->smartforge->add_key('consent_request_version_cookies', ['consent_request_version_id', 'cookie_id'], 'consent_request_version_cookies');
        }
    }


    private function updateRte()
    {
        ee()->db->where('name', 'Rte')->update('fieldtypes', ['version' => '2.1.0']);

        ee()->db->where('module_name', 'Rte')->update('modules', ['module_version' => '2.1.0']);

        ee()->db->where('class', 'Rte')
            ->where('method', 'get_js')
            ->delete('actions');

        ee()->db->where('class', 'Rte_ext')->delete('extensions');

        if (ee()->db->table_exists('rte_toolsets') && ! ee()->db->field_exists('toolset_type', 'rte_toolsets')) {
            $fields = [
                'toolset_type' => array(
                    'type' => 'varchar',
                    'constraint' => 32,
                ),
            ];
            ee()->load->dbforge();
            ee()->dbforge->add_column('rte_toolsets', $fields);
            
            // Then we'll update each of the models with the setting
            $configs = ee('Model')->get('rte:Toolset')->all();

            foreach ($configs as &$config) {
                $config->toolset_type = 'ckeditor';
                $config->save();
            }

            //install Redactor toolsets
            $toolbars = ee('rte:RedactorService')->defaultToolbars();
            foreach ($toolbars as $name => $toolbar) {
                $config_settings = array_merge(ee('rte:RedactorService')->defaultConfigSettings(), array('toolbar' => $toolbar));
                $config = ee('Model')->make('rte:Toolset');
                $config->toolset_name = $name;
                $config->toolset_type = 'redactor';
                $config->settings = $config_settings;
                $config->save();
            }
        }
    }

    private function livePreviewCsrfExcempt()
    {
        ee()->db->where(['class' => 'Channel', 'method' => 'live_preview'])->update(
            'actions',
            [
                'csrf_exempt' => '1'
            ]
        );
    }

    private function recaptchaCsrfExcempt()
    {
        ee()->db->where(['class' => 'Member', 'method' => 'recaptcha_check'])->update(
            'actions',
            [
                'csrf_exempt' => '1'
            ]
        );
    }
    
    // Add in allow_preview y/n field so that Channels can have live preview disabled as a toggle
    private function _addAllowPreview()
    {
        if (!ee()->db->field_exists('allow_preview', 'channels')) {
            ee()->smartforge->add_column(
                'channels',
                array(
                    'allow_preview' => array(
                        'type' => 'CHAR',
                        'constraint' => 1,
                        'default' => 'y',
                        'null' => false,
                    )
                )
            );

            ee()->db->update('channels', ['allow_preview' => 'y']);
        }
    }

    private function longerWatermarkImagePath()
    {
        $fields = array(
            'wm_image_path' => array(
                'name' => 'wm_image_path',
                'type' => 'varchar',
                'constraint' => '255',
                'null' => true,
                'default' => null
            ),
            'wm_test_image_path' => array(
                'name' => 'wm_test_image_path',
                'type' => 'varchar',
                'constraint' => '255',
                'null' => true,
                'default' => null
            )
        );

        ee()->smartforge->modify_column('file_watermarks', $fields);
    }

    private function addDismissedProBannerToMember()
    {
        if (!ee()->db->field_exists('dismissed_pro_banner', 'members')) {
            ee()->smartforge->add_column(
                'members',
                [
                    'dismissed_pro_banner' => [
                        'type' => 'char',
                        'constraint' => 1,
                        'default' => 'n',
                        'null' => false
                    ]
                ]
            );
        }
    }
}

// EOF
