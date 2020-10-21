<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_6_0_0_b_3;

/**
 * Update
 */
class Updater {

    var $version_suffix = '';

    /**
     * Do Update
     *
     * @return TRUE
     */
    public function do_update()
    {
        $steps = new \ProgressIterator([
            'modifyPagesColumn'
        ]);

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function modifyPagesColumn()
    {
        $mod = ee()->smartforge->modify_column('sites', [
            'site_pages' => [
                'name' => 'site_pages',
                'type' => 'MEDIUMTEXT',
                'null' => false
            ]
        ]);
    }

}

// EOF
