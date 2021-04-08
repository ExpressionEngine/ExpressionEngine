<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_6_1_0;

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
        ]);

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addConsentLogColumns()
    {
        if (!ee()->db->field_exists('ip_address', 'consent_audit_log')) {
            ee()->smartforge->add_column(
                'consent_audit_log',
                array(
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


}

// EOF
