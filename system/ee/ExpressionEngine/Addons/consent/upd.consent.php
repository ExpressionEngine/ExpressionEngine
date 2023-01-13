<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Consent Module update class
 */
class Consent_upd
{
    public $version;

    public function __construct()
    {
        ee()->load->dbforge();
        $addon = ee('Addon')->get('consent');
        $this->version = $addon ? $addon->getVersion() : '1.0.0';
    }

    /**
     * Module Installer
     *
     * @return	bool
     */
    public function install()
    {
        ee('Model')->make('Module', [
            'module_name' => 'Consent',
            'module_version' => $this->version,
            'has_cp_backend' => 'n',
        ])->save();

        $actions = [
            'grantConsent',
            'submitConsent',
            'withdrawConsent',
        ];

        foreach ($actions as $action) {
            ee('Model')->make('Action', [
                'class' => 'Consent',
                'method' => $action,
            ])->save();
        }

        return true;
    }

    /**
     * Module Uninstaller
     *
     * @return	bool
     */
    public function uninstall()
    {
        $module = ee('Model')->get('Module')
            ->filter('module_name', 'Consent')
            ->first();

        ee('Model')->get('Action')
            ->filter('class', 'Consent')
            ->delete();

        ee('db')->where('module_id', $module->module_id)
            ->delete('module_member_roles');

        $module->delete();

        return true;
    }

    /**
     * Module Updater
     *
     * @param string $current Currently installed version number
     * @return	bool
     */
    public function update($current = '')
    {
        return true;
    }
}
// END CLASS

// EOF
