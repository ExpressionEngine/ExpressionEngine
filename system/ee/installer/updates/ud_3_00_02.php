<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_3_0_2;

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
                '_update_member_field_schema',
            )
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    /**
     * Sets three columns to allow NULL and to default to NULL. This matches
     * the schema we install.
     */
    private function _update_member_field_schema()
    {
        ee()->smartforge->modify_column('member_fields', array(
            'm_field_maxl' => array(
                'type' => 'smallint(3)',
                'null' => true,
            ),
            'm_field_width' => array(
                'type' => 'varchar(6)',
                'null' => true,
            ),
            'm_field_order' => array(
                'type' => 'int(3)',
                'null' => true,
            ),
        ));

        foreach (array('m_field_maxl', 'm_field_width', 'm_field_order') as $col) {
            ee()->db->query("ALTER TABLE exp_member_fields ALTER COLUMN " . $col . " SET DEFAULT NULL");
        }
    }
}
/* END CLASS */

// EOF
