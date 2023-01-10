<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_2_7_3;

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
        ee()->load->dbforge();

        $steps = new \ProgressIterator(
            array(
                '_update_email_db_columns',
            )
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    /**
     * Change email columns to varchar(75)
     * @return void
     */
    private function _update_email_db_columns()
    {
        $changes = array(
            'members' => 'email',
            'email_cache' => 'from_email',
            'email_console_cache' => 'recipient',
        );

        foreach ($changes as $table => $column) {
            ee()->smartforge->modify_column(
                $table,
                array(
                    $column => array(
                        'name' => $column,
                        'type' => 'VARCHAR',
                        'constraint' => 75,
                        'null' => false
                    )
                )
            );
        }
    }
}
/* END CLASS */

// EOF
