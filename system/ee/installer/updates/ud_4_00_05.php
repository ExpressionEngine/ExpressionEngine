<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_4_0_5;

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
                'fixGridModifierParsing'
            )
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    /**
     * Change value of Grid fields to be a string with a single space again to
     * keep modifier parsing working
     */
    private function fixGridModifierParsing()
    {
        $grid_fields = ee('Model')->get('ChannelField')
            ->fields('field_id', 'legacy_field_data')
            ->filter('field_type', 'grid')
            ->all();

        foreach ($grid_fields as $field) {
            $column = 'field_id_' . $field->getId();

            ee()->db
                ->where($column, '')
                ->or_where($column, null)
                ->update(
                    $field->getDataStorageTable(),
                    [$column => ' ']
                );
        }
    }
}
// END CLASS

// EOF
