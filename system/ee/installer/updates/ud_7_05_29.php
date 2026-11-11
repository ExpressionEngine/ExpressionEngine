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
     * Create independent MFA approvals. Old remembered tokens confer no approval.
     *
     * @return bool
     */
    public function do_update()
    {
        ee()->load->library('smartforge');

        $steps = new \ProgressIterator([
            'addMfaSessionsTable',
        ]);

        foreach ($steps as $step) {
            if ($this->$step() === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create independent MFA approvals without trusting old remembered tokens.
     *
     * @return bool
     */
    private function addMfaSessionsTable()
    {
        unset(ee()->db->data_cache['table_names']);

        if (!ee()->db->table_exists('mfa_sessions')) {
            ee()->dbforge->add_field([
                'token_hash' => ['type' => 'char', 'constraint' => 64, 'null' => false],
                'member_id' => ['type' => 'int', 'constraint' => 10, 'null' => false],
                'site_id' => ['type' => 'int', 'constraint' => 4, 'null' => false],
                'verified_at' => ['type' => 'bigint', 'constraint' => 10, 'unsigned' => true, 'null' => false],
                'expires_at' => ['type' => 'bigint', 'constraint' => 10, 'unsigned' => true, 'null' => false],
                'issued_session_hash' => ['type' => 'char', 'constraint' => 64, 'null' => false],
                'credential_hash' => ['type' => 'char', 'constraint' => 64, 'null' => false, 'default' => '']
            ]);
            ee()->dbforge->add_key('token_hash', true);
            ee()->dbforge->add_key('member_id');
            ee()->dbforge->add_key('expires_at');
            if (!ee()->smartforge->create_table('mfa_sessions')) {
                return false;
            }
        }

        return true;
    }
}
