<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_6_0_2;

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
            'ensureSuperAdminLocked',
            'addCategoryParentKey',
        ]);

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function ensureSuperAdminLocked()
    {
        ee()->db->where('role_id', '1')->update(
            'roles',
            [
                'is_locked' => 'y'
            ]
        );
    }

    private function addCategoryParentKey()
    {
        ee()->smartforge->add_key('categories', 'parent_id');
    }

}

// EOF
