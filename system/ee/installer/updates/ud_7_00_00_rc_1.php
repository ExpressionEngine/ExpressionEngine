<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_0_0_rc_1;

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
                'prepMajorUpgrade',
                'addFileDataTable',
                'addFileManagerViewsTable',
                'addFilesTableColumns',
                'addFileUsageTable',
                'modifyUploadPrefsTable',
                'addEntryManagerViewsKeys',
                'setFileManagerCompatibilityMode',
                'installPro',
                'addProTrialSeenToSession',
                'addDockPermissionsToRoles'
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function prepMajorUpgrade()
    {
        ee('Updater/PrepMajorUpgrade')->prepMajorIfApplicable(7);
    }

    private function addFileDataTable()
    {
        if (ee()->db->table_exists('file_data')) {
            return;
        }

        // Create table
        ee()->dbforge->add_field(
            [
                'file_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => false,
                ],
            ]
        );
        ee()->dbforge->add_key('file_id', true);
        ee()->smartforge->create_table('file_data');

        ee('db')->query('INSERT INTO exp_file_data (file_id) SELECT file_id FROM exp_files');
    }

    private function addFileManagerViewsTable()
    {
        if (! ee()->db->table_exists('file_manager_views')) {
            ee()->dbforge->add_field(
                [
                    'view_id' => [
                        'type' => 'int',
                        'constraint' => 10,
                        'unsigned' => true,
                        'null' => false,
                        'auto_increment' => true
                    ],
                    'viewtype' => [
                        'type' => 'varchar',
                        'constraint' => 10,
                        'null' => false,
                        'default' => 'list',
                    ],
                    'upload_id' => [
                        'type' => 'int',
                        'constraint' => 6,
                        'unsigned' => true,
                        'null' => false,
                    ],
                    'member_id' => [
                        'type' => 'int',
                        'constraint' => 10,
                        'unsigned' => true,
                        'null' => false,
                    ],
                    'name' => [
                        'type' => 'varchar',
                        'constraint' => 128,
                        'null' => false,
                        'default' => '',
                    ],
                    'columns' => [
                        'type' => 'text',
                        'null' => false
                    ]
                ]
            );
            ee()->dbforge->add_key('view_id', true);
            ee()->dbforge->add_key(['viewtype', 'upload_id', 'member_id']);
            ee()->smartforge->create_table('file_manager_views');
        }
    }

    private function addFilesTableColumns()
    {
        if (! ee()->db->field_exists('model_type', 'files')) {
            ee()->smartforge->add_column(
                'files',
                [
                    'model_type' => [
                        'type' => 'enum',
                        'constraint' => "'File','Directory'",
                        'default' => 'File',
                        'null' => false
                    ]
                ],
                'file_id'
            );
            ee()->smartforge->add_key('files', 'model_type');
        }

        if (! ee()->db->field_exists('file_type', 'files')) {
            ee()->smartforge->add_column(
                'files',
                [
                    'file_type' => [
                        'type' => 'varchar',
                        'constraint' => '50',
                        'default' => null,
                        'null' => true
                    ]
                ],
                'mime_type'
            );
            ee()->smartforge->add_key('files', 'file_type');
        }

        if (! ee()->db->field_exists('directory_id', 'files')) {
            ee()->smartforge->add_column(
                'files',
                [
                    'directory_id' => [
                        'type' => 'int',
                        'constraint' => 10,
                        'default' => 0,
                        'unsigned' => true,
                        'null' => false
                    ]
                ],
                'upload_location_id'
            );
            ee()->smartforge->add_key('files', 'directory_id');
        }

        if (! ee()->db->field_exists('total_records', 'files')) {
            ee()->smartforge->add_column(
                'files',
                [
                    'total_records' => [
                        'type' => 'int',
                        'constraint' => 10,
                        'default' => 0,
                        'unsigned' => true,
                        'null' => false
                    ]
                ]
            );
        }
    }

    private function modifyUploadPrefsTable()
    {
        ee()->smartforge->modify_column(
            'upload_prefs',
            array(
                'allowed_types' => array(
                    'name' => 'allowed_types',
                    'type' => 'varchar',
                    'constraint' => 100,
                    'default' => 'img',
                    'null' => false
                )
            )
        );

        if (! ee()->db->field_exists('adapter', 'upload_prefs')) {
            ee()->smartforge->add_column(
                'upload_prefs',
                [
                    'adapter' => [
                        'type' => 'varchar',
                        'constraint' => 50,
                        'default' => 'local',
                        'null' => false
                    ]
                ],
                'name'
            );

            ee()->smartforge->add_column(
                'upload_prefs',
                [
                    'adapter_settings' => [
                        'type' => 'text',
                        'default' => null,
                        'null' => true
                    ]
                ],
                'adapter'
            );
        }

        if (! ee()->db->field_exists('subfolders_on_top', 'upload_prefs')) {
            ee()->smartforge->add_column(
                'upload_prefs',
                [
                    'subfolders_on_top' => [
                        'type' => 'enum',
                        'constraint' => "'y','n'",
                        'default' => 'y',
                        'null' => false
                    ]
                ],
                'allowed_types'
            );
        }

        if (! ee()->db->field_exists('allow_subfolders', 'upload_prefs')) {
            ee()->smartforge->add_column(
                'upload_prefs',
                [
                    'allow_subfolders' => [
                        'type' => 'enum',
                        'constraint' => "'y','n'",
                        'default' => 'n',
                        'null' => false
                    ]
                ],
                'allowed_types'
            );
        }
    }

    private function addFileUsageTable()
    {
        if (ee()->db->table_exists('file_usage')) {
            return;
        }

        // Create table
        ee()->dbforge->add_field(
            [
                'file_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => false,
                ],
                'entry_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => false,
                    'default' => 0
                ],
                'cat_id' => [
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => false,
                    'default' => 0
                ],
            ]
        );
        ee()->dbforge->add_key('file_id');
        ee()->dbforge->add_key('entry_id');
        ee()->dbforge->add_key('cat_id');
        ee()->smartforge->create_table('file_usage');
    }

    private function addEntryManagerViewsKeys()
    {
        ee()->smartforge->add_key('entry_manager_views', ['channel_id', 'member_id']);

        ee()->smartforge->add_key('entry_manager_views', ['channel_id', 'member_id']);
    }

    private function setFileManagerCompatibilityMode()
    {
        ee('Model')->make('Config', [
            'site_id' => 0,
            'key' => 'file_manager_compatibility_mode',
            'value' => 'y'
        ])->save();

        ee('Model')->make('Config', [
            'site_id' => 0,
            'key' => 'warn_file_manager_compatibility_mode',
            'value' => 'y'
        ])->save();
    }

    private function installPro()
    {
        $addon = ee('Addon')->get('pro');
        if (! $addon or ! $addon->isInstalled()) {
            $enableDock = bool_config_item('enable_dock');
            if (!isset(ee()->addons)) {
                ee()->load->library('addons');
            }
            ee()->addons->install_modules(['pro']);
            ee()->config->update_site_prefs(['enable_dock' => $enableDock ? 'y' : 'n'], 'all');
        }
    }

    private function addProTrialSeenToSession()
    {
        if (!ee()->db->field_exists('pro_banner_seen', 'sessions')) {
            ee()->smartforge->add_column(
                'sessions',
                [
                    'pro_banner_seen' => [
                        'type' => 'char',
                        'constraint' => 1,
                        'default' => 'n',
                        'null' => false
                    ]
                ]
            );
        }
    }

    private function addDockPermissionsToRoles()
    {
        $insert = [];
        //get Pro module ID
        $proIdQuery = ee('db')->select('module_id')->from('exp_modules')->where('module_name', 'Pro')->get();
        $proModuleId = $proIdQuery->row('module_id');

        $canAccessProQuery = ee('db')->select('role_id')->from('module_member_roles')->where('module_id', $proModuleId)->get();
        $roles = [1];
        foreach ($canAccessProQuery->result_array() as $row)
        {
            $roles[] = $row['role_id'];
        }

        $sites = ee()->db->select('site_id')->get('sites')->result_array();

        foreach ($roles as $roleId) {
            foreach ($sites as $site) {
                $insert[] = [
                    'role_id' => $roleId,
                    'site_id' => $site['site_id'],
                    'permission' => 'can_access_dock'
                ];
            }
        }

        if (! empty($insert)) {
            ee()->db->insert_batch('permissions', $insert);
        }
    }
}

// EOF
