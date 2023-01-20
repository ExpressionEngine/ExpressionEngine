<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_2_3;

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
                'addTemplateEngineToTemplates',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addTemplateEngineToTemplates()
    {
        if (!ee()->db->field_exists('template_engine', 'templates')) {
            ee()->smartforge->add_column(
                'templates',
                [
                    'template_engine' => [
                        'type' => 'varchar',
                        'constraint' => 24,
                        'default' => null,
                        'null' => true
                    ]
                ],
                'template_type'
            );
        }
    }
}

// EOF
