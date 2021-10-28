<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_3_3_1;

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
            array(
                'use_site_default_localization_settings',
                'set_encryption_key',
                'fixCategoryFields'
            )
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    /**
     * Redo to allow our localized date and time settings to have NULL values
     * in the db.
     *
     * @return void
     */
    private function use_site_default_localization_settings()
    {
        // Allow NULL and make that the default
        ee()->smartforge->modify_column(
            'members',
            array(
                'timezone' => array(
                    'name' => 'timezone',
                    'type' => 'varchar',
                    'constraint' => 50,
                    'null' => true,
                    'default' => null
                ),
                'date_format' => array(
                    'name' => 'date_format',
                    'type' => 'varchar',
                    'constraint' => 8,
                    'null' => true,
                    'default' => null
                ),
                'time_format' => array(
                    'name' => 'time_format',
                    'type' => 'char',
                    'constraint' => 2,
                    'null' => true,
                    'default' => null
                ),
                'include_seconds' => array(
                    'name' => 'include_seconds',
                    'type' => 'char',
                    'constraint' => 1,
                    'null' => true,
                    'default' => null
                )
            )
        );
    }

    /**
     * Create a valid Encryption Key
     */
    private function set_encryption_key()
    {
        $encryption_key = ee()->config->item('encryption_key');
        if (empty($encryption_key)) {
            ee()->config->update_site_prefs(
                array('encryption_key' => ee('Encrypt')->generateKey()),
                'all'
            );
        }
    }

    // Adds category fields
    private function fixCategoryFields()
    {
        if (! ee()->db->field_exists('legacy_field_data', 'category_fields')) {
            ee()->smartforge->add_column(
                'category_fields',
                array(
                    'legacy_field_data' => array(
                        'type' => 'CHAR(1)',
                        'null' => false,
                        'default' => 'n'
                    )
                )
            );
            ee()->db->update('category_fields', array('legacy_field_data' => 'y'));
        }
    }
}

// EOF
