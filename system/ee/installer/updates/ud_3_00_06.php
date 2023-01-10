<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_3_0_6;

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
                '_addAllowPreview',
                'addStickyChannelPreference',
                '_comment_formatting'
            )
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    /**
     * Increase the column for storing comment formatting
     */
    private function _comment_formatting()
    {
        ee()->smartforge->modify_column(
            'channels',
            array(
                'comment_text_formatting' => array(
                    'type' => 'char',
                    'constraint' => 40,
                    'null' => false,
                    'default' => 'xhtml',
                ),
            )
        );
    }

    private function _addAllowPreview()
    {
        if (!ee()->db->field_exists('allow_preview', 'channels')) {
            ee()->smartforge->add_column(
                'channels',
                array(
                    'allow_preview' => array(
                        'type' => 'CHAR',
                        'constraint' => 1,
                        'default' => 'y',
                        'null' => FALSE,
                    )
                )
            );

            ee()->db->update('channels', ['allow_preview' => 'y']);
        }
    }

    private function addStickyChannelPreference()
    {
        if (!ee()->db->field_exists('sticky_enabled', 'channels')) {
            ee()->smartforge->add_column(
                'channels',
                array(
                    'sticky_enabled' => array(
                        'type' => 'char',
                        'constraint' => 1,
                        'null' => false,
                        'default' => 'n'
                    )
                )
            );

            ee()->db->update('channels', ['sticky_enabled' => 'y']);
        }
    }
}
/* END CLASS */

// EOF
