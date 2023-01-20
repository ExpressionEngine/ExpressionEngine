<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_3_4_5;

/**
 * Update
 */
class Updater
{
    public $version_suffix = '';

    public $affected_tables = ['actions', 'modules'];

    /**
     * Do Update
     *
     * @return TRUE
     */
    public function do_update()
    {
        $steps = new \ProgressIterator(
            array(
                'addRelationshipModule',
            )
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addRelationshipModule()
    {
        $installed = ee()->db->get_where('modules', array('module_name' => 'Relationship'));

        if ($installed->num_rows() > 0) {
            return;
        }

        ee()->db->insert(
            'modules',
            array(
                'module_name' => 'Relationship',
                'module_version' => '1.0.0',
                'has_cp_backend' => 'n',
                'has_publish_fields' => 'n'
            )
        );

        ee()->db->insert_batch(
            'actions',
            array(
                array(
                    'class' => 'Relationship',
                    'method' => 'entryList'
                )
            )
        );
    }
}

// EOF
