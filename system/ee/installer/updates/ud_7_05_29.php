<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_5_29;

class Updater
{
    public $version_suffix = '';

    /**
     * Add verification timestamps to remembered logins without trusting old tokens.
     *
     * @return bool
     */
    public function do_update()
    {
        // A retry can retain field metadata from before the column was added.
        unset(ee()->db->data_cache['field_names']['remember_me']);

        if (ee()->db->field_exists('mfa_verified_at', 'remember_me')) {
            return true;
        }

        ee()->load->library('smartforge');

        return ee()->smartforge->add_column('remember_me', [
            'mfa_verified_at' => [
                'type' => 'bigint',
                'constraint' => 10,
                'unsigned' => true,
                'null' => false,
                'default' => 0
            ]
        ]);
    }
}
