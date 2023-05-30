<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_6_2_7;

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
                'addSearchNoResultsPage',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addSearchNoResultsPage()
    {
        if (!ee()->db->field_exists('no_result_page', 'search')) {
            ee()->smartforge->add_column(
                'search',
                array(
                    'no_result_page' => array(
                        'type' => 'varchar',
                        'constraint' => '70',
                    )
                )
            );
        }
        
        ee()->db->where('module_name', 'Search')->update('modules', ['module_version' => '2.3.0']);
    }
}

// EOF
