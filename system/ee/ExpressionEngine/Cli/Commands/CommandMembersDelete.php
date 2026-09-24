<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Cli\Commands;

use ExpressionEngine\Cli\Cli;
use ExpressionEngine\Cli\CliOptionsTrait;

/**
 * Command to delete a member
 */
class CommandMembersDelete extends Cli
{
    use CliOptionsTrait;

    /**
     * name of command
     * @var string
     */
    public $name = 'Delete Member';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'members:delete';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php members:delete --member_id=<member_id> [--force]';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'member_id,i:' => 'command_members_member_id',
        'force,f' => 'command_sites_force_delete',
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $members = [];
        $builtChoices = false;

        if ($this->option('--member_id', false) === false) {
            $query = ee('Model')->get('Member')->order('username', 'asc');
            foreach ($query->all() as $m) {
                $label = trim(($m->username ?: '') . ' (' . ($m->screen_name ?: '-') . ')');
                $members[$m->member_id] = $label;
            }
            $builtChoices = true;
        }

        $this->data['member_id'] = $this->getOptionValue('member_id', [
            'type' => 'select',
            'desc' => 'command_members_delete_ask',
            'choices' => $members,
            'default' => null,
            'required' => true
        ]);

        if ($builtChoices && !array_key_exists($this->data['member_id'], $members)) {
            $this->fail(lang('command_members_not_found'));
        }

        $member = ee('Model')->get('Member', $this->data['member_id'])->first();
        if (!$member) {
            $this->fail(lang('command_members_not_found'));
        }

        // You can't delete the only Super 
        if ($member->role_id == 1) {
            $totalSuperAdmins = ee('Model')->get('Member')->filter('role_id', 1)->count();
            if ($totalSuperAdmins <= 1) {
                show_error(lang('cannot_delete_super_admin'));
            }
        }

        $confirmation = $this->option('--force', false);
        if (!$confirmation) {
            $display = $member->username ?: ('ID ' . $member->member_id);
            $confirmation = $this->confirm(sprintf(lang('command_members_delete_confirm'), $display), false);
        }
        if (!$confirmation) {
            $this->fail(lang('command_members_not_deleted'));
        }

        $this->info(lang('command_members_deleting_member'));

        $member->delete();

        $this->complete(lang('command_members_deleted'));
    }
}