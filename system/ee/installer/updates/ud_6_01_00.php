<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
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
        $steps = new \ProgressIterator(
            array(
                'addAllowPreview',
            )
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    // Add in allow_preview y/n field so that Channels can have live preview disabled as a toggle
    private function addAllowPreview()
    {

        ee()->smartforge->add_column(
            'channels',
            array(
                'allow_preview' => array(
                    'type' => 'CHAR',
                    'constraint' => 1,
                    'default' => 'y',
                    'null' => false,
                )
            )
        );
    }
}
// END CLASS

// EOF
