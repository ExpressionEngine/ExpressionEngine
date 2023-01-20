<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_4_3_0;

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
        $steps = new \ProgressIterator(
            [
                'addConsentTables',
                'addConsentModerationPermissions',
                'addMemberFieldAnonExcludeColumn',
                'addCookieSettingsTable',
                'installConsentModule',
                'addSessionAuthTimeoutColumn',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addConsentTables()
    {
        ee()->dbforge->add_field(
            [
                'consent_request_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => false,
                    'auto_increment' => true
                ],
                'consent_request_version_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => true,
                    'default' => null
                ],
                'user_created' => [
                    'type' => 'char',
                    'constraint' => 1,
                    'null' => false,
                    'default' => 'n',
                ],
                'title' => [
                    'type' => 'varchar',
                    'constraint' => 200,
                    'null' => false
                ],
                'consent_name' => [
                    'type' => 'varchar',
                    'constraint' => 50,
                    'null' => false
                ],
                'double_opt_in' => [
                    'type' => 'char',
                    'constraint' => 1,
                    'null' => false,
                    'default' => 'n'
                ],
                'retention_period' => [
                    'type' => 'varchar',
                    'constraint' => 32,
                    'null' => true,
                ],
            ]
        );
        ee()->dbforge->add_key('consent_request_id', true);
        ee()->dbforge->add_key('consent_name');
        ee()->smartforge->create_table('consent_requests');

        ee()->dbforge->add_field(
            [
                'consent_request_version_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => false,
                    'auto_increment' => true
                ],
                'consent_request_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => false
                ],
                'request' => [
                    'type' => 'mediumtext',
                    'null' => true
                ],
                'request_format' => [
                    'type' => 'tinytext',
                    'null' => true
                ],
                'create_date' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'null' => false,
                    'default' => 0
                ],
                'author_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => false,
                    'default' => 0
                ],
            ]
        );
        ee()->dbforge->add_key('consent_request_version_id', true);
        ee()->dbforge->add_key('consent_request_id');
        ee()->smartforge->create_table('consent_request_versions');

        ee()->dbforge->add_field(
            [
                'consent_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => false,
                    'auto_increment' => true
                ],
                'consent_request_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => false
                ],
                'consent_request_version_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => false
                ],
                'member_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => false
                ],
                'request_copy' => [
                    'type' => 'mediumtext',
                    'null' => true
                ],
                'request_format' => [
                    'type' => 'tinytext',
                    'null' => true
                ],
                'consent_given' => [
                    'type' => 'char',
                    'constraint' => 1,
                    'null' => false,
                    'default' => 'n'
                ],
                'consent_given_via' => [
                    'type' => 'varchar',
                    'constraint' => 32,
                    'null' => true
                ],
                'expiration_date' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'null' => true
                ],
                'response_date' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'null' => true
                ],
            ]
        );
        ee()->dbforge->add_key('consent_id', true);
        ee()->dbforge->add_key('consent_request_version_id');
        ee()->dbforge->add_key('member_id');
        ee()->smartforge->create_table('consents');

        ee()->dbforge->add_field(
            [
                'consent_audit_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => false,
                    'auto_increment' => true
                ],
                'consent_request_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => false
                ],
                'member_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => false
                ],
                'action' => [
                    'type' => 'text',
                    'null' => false
                ],
                'log_date' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'null' => false,
                    'default' => 0
                ],
            ]
        );
        ee()->dbforge->add_key('consent_audit_id', true);
        ee()->dbforge->add_key('consent_request_id');
        ee()->smartforge->create_table('consent_audit_log');
    }

    private function addConsentModerationPermissions()
    {
        ee()->smartforge->add_column(
            'member_groups',
            array(
                'can_manage_consents' => array(
                    'type' => 'CHAR',
                    'constraint' => 1,
                    'default' => 'n',
                    'null' => false,
                )
            )
        );

        // Only assume super admins can moderate consent requests
        ee()->db->update('member_groups', array('can_manage_consents' => 'y'), array('group_id' => 1));
    }

    private function addMemberFieldAnonExcludeColumn()
    {
        ee()->smartforge->add_column(
            'member_fields',
            [
                'm_field_exclude_from_anon' => [
                    'type' => 'CHAR(1)',
                    'null' => false,
                    'default' => 'n'
                ]
            ],
            'm_field_show_fmt'
        );
    }

    // this is also in 6.1.0, but we need it here, otherwise consents will not install properly
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

    private function installConsentModule()
    {
        $addon = ee('Addon')->get('consent');

        if (! $addon or ! $addon->isInstalled()) {
            if (!isset(ee()->addons)) {
                ee()->load->library('addons');
            }
            ee()->addons->install_modules(['consent']);

            try {
                $addon = ee('Addon')->get('consent');
                $addon->installConsentRequests();
            } catch (\Exception $e) {
                // probably just ran the update again
            }
        }
    }

    private function addSessionAuthTimeoutColumn()
    {
        ee()->smartforge->add_column(
            'sessions',
            [
                'auth_timeout' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => false,
                    'default' => 0
                ]
            ],
            'sess_start'
        );
    }
}

// EOF
