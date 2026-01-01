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
use ExpressionEngine\Service\Model\Collection;

/**
 * Command to add a new category group
 */
class CommandCategoryGroupAdd extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Add Category Group';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'cgroup:add';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php cgroup:add [--site=<site_id>] [--name="category_group_name"]';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'site,s:' => 'command_fields_list_option_site',
        'name,n:' => 'command_category_groups_name'
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $this->data['site_id'] = $this->getMsmSiteId('first');

        $this->data['group_name'] = $this->getOptionOrAsk('--name', lang('command_category_groups_name'), '', true);

        $this->info(lang('command_category_groups_adding'));

        $category_group = ee('Model')->make('CategoryGroup', $this->data);
        $validation = $category_group->validate();
        if ($validation->failed()) {
            foreach ($validation->getAllErrors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }
            $this->fail(lang('command_category_groups_not_added'));
        }

        $category_group->save();
        $this->complete(lang('command_category_groups_added'));
    }
}
