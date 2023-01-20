<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_4_3_2;

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
                'memberDataTableCleanup',
                'updateFieldFmtOptionForMemberFields',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    /**
    * Fields created in v2 didn't have a m_field_ft_x column added,
    * so we need to add it for those legacy member fields
    */
    private function memberDataTableCleanup()
    {
        $new_column = array();
        $id_ids = array();
        $ft_ids = array();
        $member_data_columns = ee()->db->list_fields('member_data');

        foreach ($member_data_columns as $column) {
            if (strncmp('m_field_id_', $column, 11) == 0) {
                $id_ids[] = substr($column, 11);
            } elseif (strncmp('m_field_ft_', $column, 11) == 0) {
                $ft_ids[] = substr($column, 11);
            }
        }

        $make = array_diff($id_ids, $ft_ids);

        foreach ($make as $id) {
            $new_column['m_field_ft_' . $id] = array('type' => 'tinytext');
            ee()->smartforge->add_column('member_data', $new_column, 'm_field_id_' . $id);
        }
    }

    private function updateFieldFmtOptionForMemberFields()
    {
        ee()->smartforge->modify_column('member_fields', array(
            'm_field_fmt' => array(
                'type' => 'varchar',
                'constraint' => 40,
            ),
        ));
    }
}

// EOF
