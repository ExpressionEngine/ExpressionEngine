<?php

/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
*/

use ExpressionEngine\Service\Addon\Installer;
use ExpressionEngine\Library\Filesystem\Filesystem;

class Pro_upd extends Installer
{
    public $actions = [
        [
            'class' => 'Pro',
            'method' => 'setCookie'
        ],
        [
            'class' => 'Pro',
            'method' => 'qrCode'
        ],
        [
            'class' => 'Pro',
            'method' => 'validateMfa'
        ],
        [
            'class' => 'Pro',
            'method' => 'invokeMfa'
        ],
        [
            'class' => 'Pro',
            'method' => 'enableMfa'
        ],
        [
            'class' => 'Pro',
            'method' => 'disableMfa'
        ],
        [
            'class' => 'Pro',
            'method' => 'resetMfa'
        ],
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function install()
    {
        $installed = parent::install();
        ee()->config->update_site_prefs([
            'enable_dock' => 'y',
            'enable_frontedit' => 'y',
            'automatic_frontedit_links' => 'y',
            'enable_mfa' => 'y',
        ]);
        if ($installed) {
            //Set the necessary config values
            if (ee()->config->item('autosave_interval_seconds') === false) {
                ee()->config->update_site_prefs([
                    'autosave_interval_seconds' => 10
                ]);
            }

            //Install tables, unless already installed
            ee()->load->dbforge();
            ee()->load->library('smartforge');

            if (!ee()->db->table_exists('dashboard_widgets')) {
                ee()->dbforge->add_field(
                    [
                        'widget_id' => [
                            'type' => 'int',
                            'constraint' => 10,
                            'null' => false,
                            'unsigned' => true,
                            'auto_increment' => true
                        ],
                        'widget_name' => [
                            'type' => 'varchar',
                            'constraint' => 50,
                            'null' => true,
                            'default' => null
                        ],
                        'widget_data' => [
                            'type' => 'mediumtext',
                            'null' => true
                        ],
                        'widget_type' => [
                            'type' => 'varchar',
                            'constraint' => 10,
                            'null' => false
                        ],
                        'widget_source' => [
                            'type' => 'varchar',
                            'constraint' => 50,
                            'null' => false
                        ],
                        'widget_file' => [
                            'type' => 'varchar',
                            'constraint' => 100,
                            'null' => true,
                            'default' => null
                        ]
                    ]
                );
                ee()->dbforge->add_key('widget_id', true);
                ee()->smartforge->create_table('dashboard_widgets');
            }

            if (!ee()->db->table_exists('dashboard_layout_widgets')) {
                ee()->dbforge->add_field(
                    [
                        'layout_id' => [
                            'type' => 'int',
                            'constraint' => 10,
                            'null' => false,
                            'unsigned' => true
                        ],
                        'widget_id' => [
                            'type' => 'int',
                            'constraint' => 10,
                            'null' => false,
                            'unsigned' => true
                        ]
                    ]
                );
                ee()->smartforge->create_table('dashboard_layout_widgets');
                ee()->smartforge->add_key('dashboard_layout_widgets', ['layout_id', 'widget_id'], 'layouts_widgets');
            }

            if (!ee()->db->table_exists('docks')) {
                ee()->dbforge->add_field(
                    [
                        'dock_id' => [
                            'type' => 'int',
                            'constraint' => 10,
                            'null' => false,
                            'unsigned' => true,
                            'auto_increment' => true
                        ],
                        'site_id' => [
                            'type' => 'int',
                            'constraint' => 10,
                            'unsigned' => true,
                            'null' => true,
                            'default' => null
                        ]
                    ]
                );
                ee()->dbforge->add_key('dock_id', true);
                ee()->smartforge->create_table('docks');
            }

            if (!ee()->db->table_exists('prolets')) {
                ee()->dbforge->add_field(
                    [
                        'prolet_id' => [
                            'type' => 'int',
                            'constraint' => 10,
                            'null' => false,
                            'unsigned' => true,
                            'auto_increment' => true
                        ],
                        'source' => [
                            'type' => 'varchar',
                            'constraint' => 100,
                            'null' => true,
                            'default' => null
                        ],
                        'class' => [
                            'type' => 'varchar',
                            'constraint' => 255,
                            'null' => false
                        ]
                    ]
                );
                ee()->dbforge->add_key('prolet_id', true);
                ee()->smartforge->create_table('prolets');
            }

            if (!ee()->db->table_exists('dock_prolets')) {
                ee()->dbforge->add_field(
                    [
                        'dock_prolets_id' => [
                            'type' => 'int',
                            'constraint' => 10,
                            'null' => false,
                            'unsigned' => true,
                            'auto_increment' => true
                        ],
                        'dock_id' => [
                            'type' => 'int',
                            'constraint' => 10,
                            'null' => false,
                            'unsigned' => true
                        ],
                        'prolet_id' => [
                            'type' => 'int',
                            'constraint' => 10,
                            'null' => false,
                            'unsigned' => true
                        ]
                    ]
                );
                ee()->dbforge->add_key('dock_prolets_id', true);
                ee()->smartforge->create_table('dock_prolets');
                ee()->smartforge->add_key('dock_prolets', ['dock_id', 'prolet_id'], 'dock_prolets');
            }

            //install default widget templates
            $tmpl_group_exists = ee('Model')->get('TemplateGroup')->filter('group_name', 'pro-dashboard-widgets')->count();
            if (!$tmpl_group_exists) {
                $tmpl_group_data = [
                    'group_name' => 'pro-dashboard-widgets',
                    'is_site_default' => 'n',
                    'site_id' => ee()->config->item('site_id') ?: 1
                ];
                $group = ee('Model')->make('TemplateGroup', $tmpl_group_data)->save();

                $file_path = PATH_ADDONS . 'pro/View/widgets/sample-widget.php';
                $tmpl_info = [
                    'group_id' => $group->group_id,
                    'template_name' => 'sample-widget',
                    'template_data' => file_get_contents($file_path),
                    'template_type' => 'webpage',
                    'last_author_id' => 0,
                    'edit_date' => time(),
                    'site_id' => ee()->config->item('site_id') ?: 1
                ];

                $template = ee('Model')->make('Template', $tmpl_info)->save();
                $template->template_data = str_replace('TMPL_ID', $template->getId(), $template->template_data);
                $template->save();
            }

            // Install widgets and prolets for already installed add-ons
            foreach (ee('pro:Addon')->installed() as $addon) {
                $addon->updateDashboardWidgets();
                $addon->updateProlets();
            }

            $self = ee('pro:Addon')->get('pro');
            $self->updateDashboardWidgets();
            $self->updateProlets();

            // Install or update bundled add-ons
            if (!isset(ee()->addons)) {
                ee()->load->library('addons');
            }
            if (!isset(ee()->addons_installer)) {
                ee()->load->library('addons/addons_installer');
            }

            $fs = new Filesystem();

            $bundledAddons = [
                'low_search' => 'Low Search',
                'low_variables' => 'Low Variables'
            ];

            $bundledAddonsInstalled = [];

            $inEEInstallMode = is_dir(SYSPATH . 'ee/installer/') && (! defined('INSTALL_MODE') or INSTALL_MODE != false);

            //bundled add-ons will be handled separately, skip if installing with EE
            if ($inEEInstallMode || REQ == 'CLI') {
                return $installed;
            }

            foreach ($bundledAddons as $addonName => $addonReadableName) {
                if (!$fs->exists(PATH_THIRD . $addonName)) {
                    if (lang('module_can_not_be_found_' . $addonName) != 'module_can_not_be_found_' . $addonName) {
                        $module_lang = lang('module_can_not_be_found_' . $addonName);
                    } else {
                        $module_lang = lang('module_can_not_be_found');
                    }

                    ee('CP/Alert')->makeInline($addonName . 'NotInstalled')
                        ->asWarning()
                        ->withTitle(lang('addons_not_installed'))
                        ->addToBody($module_lang)
                        ->addToBody([$addonReadableName])
                        ->defer();
                    continue;
                }
                $addon = ee('pro:Addon')->get($addonName);
                ee()->load->add_package_path(PATH_THIRD . $addonName);
                if ($addon && $addon->isInstalled()) {
                    if ($addon->hasModule()) {
                        $class = $addon->getInstallerClass();
                        $module = ee('Model')->get('Module')->filter('module_name', ucfirst($addonName))->first();
                        $UPD = new $class();
                        if (method_exists($UPD, 'update') && $UPD->update($module->module_version) !== false) {
                            if (version_compare($module->module_version, $addon->getVersion(), '<')) {
                                $module = ee('Model')->get('Module', $module->getId())->first();
                                $module->module_version = $addon->getVersion();
                                $module->save();
                            }
                        }
                    }

                    if ($addon->hasExtension()) {
                        $class = $addon->getExtensionClass();
                        $extension = ee('Model')->get('Extension')->filter('class', $class)->first();
                        $EXT = new $class();

                        if (!empty($extension) && method_exists($EXT, 'update_extension')) {
                            $EXT->update_extension($extension->version);
                            ee()->extensions->version_numbers[$class] = $addon->getVersion();
                            $extension = ee('Model')->get('Extension', $extension->getId())->first();
                            $extension->version = $addon->getVersion();
                            $extension->save();
                        } elseif (method_exists($EXT, 'activate_extension')) {
                            $EXT->activate_extension();
                            ee()->extensions->version_numbers[$class] = $addon->getVersion();
                        }
                    }

                    $addon->updateConsentRequests();
                    $addon->updateDashboardWidgets();
                    $addon->updateProlets();
                } else {
                    ee()->addons_installer->install($addonName, 'module', false);
                    $bundledAddonsInstalled[] = $addon->getName();
                }
                ee()->load->remove_package_path(PATH_THIRD . $addonName);
            }
            if (!empty($bundledAddonsInstalled)) {
                ee('CP/Alert')->makeInline('bundledAddonsInstalled')
                    ->asSuccess()
                    ->withTitle(lang('addons_installed'))
                    ->addToBody(lang('addons_installed_desc'))
                    ->addToBody(array_values($bundledAddonsInstalled))
                    ->defer();
            }
        }
        return $installed;
    }

    public function update($current = '')
    {
        $updated = parent::update($current);

        if ($updated) {
            foreach (ee('pro:Addon')->installed() as $addon) {
                $addon->updateDashboardWidgets();
                $addon->updateProlets();
            }
        }

        return true;
    }

    public function uninstall()
    {
        $removed = parent::uninstall();
        if ($removed) {
            ee()->load->dbforge();
            ee()->db->truncate('dashboard_layouts');
            ee()->dbforge->drop_table('dashboard_widgets');
            ee()->dbforge->drop_table('dashboard_layout_widgets');
            ee()->dbforge->drop_table('docks');
            ee()->dbforge->drop_table('prolets');
            ee()->dbforge->drop_table('dock_prolets');
        }
        return $removed;
    }
}
