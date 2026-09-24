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
 * Command to add a new member
 */
class CommandMembersAdd extends Cli
{
    use CliOptionsTrait;

    /**
     * name of command
     * @var string
     */
    public $name = 'Add Member';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'members:add';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php members:add [--username="username"] [--email="email@example.com"] [--password="password"]';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'username:' => 'command_members_username',
        'email:' => 'command_members_email',
        'password:' => 'command_members_password',
        'screen_name:' => 'command_members_screen_name',
        'role:' => 'command_members_role',
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

        $this->info(lang('command_members_add_begin'));

        $member = $this->getFieldsForMembers();

        $this->data['join_date'] = ee()->localize->now;
        $member->set($this->data);
        $this->validateModel($member, lang('command_members_not_added'));

        $member->save();
        $this->complete(sprintf(lang('command_members_add_complete'), $member->username));
    }
}