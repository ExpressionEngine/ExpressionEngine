<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Cli\Commands;

use ExpressionEngine\Cli\Cli;
use ExpressionEngine\Cli\CliOptionsTrait;

/**
 * Command to edit a member
 */
class CommandMembersEdit extends Cli
{
    use CliOptionsTrait;

    /**
     * name of command
     * @var string
     */
    public $name = 'Edit Member';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'members:edit';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php members:edit [--member_id=<member_id>]';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'member_id,m:' => 'command_members_member_id',
        'fields,f:' => 'command_sites_fields',
    ];

    /**
     * whether command options are dynamic
     * @var bool
     */
    public $dynamicCommandOptions = true;

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        ee()->lang->load('members');

        $members = [];

        if ($this->option('--member_id', false) === false) {
            $membersQuery = ee('Model')->get('Member')->order('username', 'asc');
            foreach ($membersQuery->all() as $member) {
                $label = $member->username;
                if (!empty($member->screen_name)) {
                    $label .= ' (' . $member->screen_name . ')';
                }
                $members[$member->member_id] = $label;
            }
        }

        $this->data['member_id'] = $this->getOptionValue('member_id', [
            'type' => 'select',
            'desc' => 'command_members_edit_ask',
            'choices' => $members,
            'default' => null,
            'required' => true,
        ]);

        $member = ee('Model')->get('Member', $this->data['member_id'])->first();
        if (empty($member)) {
            $this->fail(lang('command_members_not_found'));
        }

        $this->info(sprintf(lang('command_members_editing'), $member->username));

        $member = $this->getFieldsForMembers($member);

        $member->set($this->data);
        $this->validateModel($member, lang('command_members_not_saved'));

        $member->save();
        $this->complete(lang('command_members_saved'));
    }
}