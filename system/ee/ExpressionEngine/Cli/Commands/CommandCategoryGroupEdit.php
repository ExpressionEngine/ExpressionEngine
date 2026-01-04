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
use ExpressionEngine\Cli\CliOptionsTrait;

/**
 * Command to edit an existing category group
 */
class CommandCategoryGroupEdit extends Cli
{
    use CliOptionsTrait;

    /**
     * name of command
     * @var string
     */
    public $name = 'Edit Category Group';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'cgroup:edit';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php cgroup:edit --group_id=<group_id> --fields="name" [--name="category_group_name"]';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'group_id,i:' => 'command_category_groups_id',
        'fields,f:' => 'command_sites_fields',
        'name,n:' => 'command_category_groups_name',
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $category_groups = ee('Model')->get('CategoryGroup')->all()->getDictionary('group_id', 'group_name');

        if ($this->option('--group_id')) {
            $this->data['group_id'] = $this->option('--group_id');
        } else {
            $this->data['group_id'] = $this->askFromList(lang('command_category_groups_edit_ask'), $category_groups, null);
        }

        if (!array_key_exists($this->data['group_id'], $category_groups)) {
            $this->fail(lang('command_category_groups_not_found'));
        }

        $category_group = ee('Model')->get('CategoryGroup', $this->data['group_id'])->first();

        $this->info(sprintf(lang('command_sites_editing_category_group'), $category_group->group_name));

        //$fields = $this->getOptionOrAsk('--fields', lang('command_sites_edit_which_fields'), 'name, label, description, color, status');
        //$fields = array_map('trim', explode(',', $fields));
        $fields = ['name'];

        foreach ($fields as $field) {
            $this->data['group_' . $field] = $this->getOptionOrAsk('--' . $field, lang('command_category_groups_ask_' . $field), $category_group->{'group_' . $field}, ($field == 'name'));
        }

        $this->info(lang('command_sites_saving_category_group'));

        $category_group->set($this->data);
        $this->validateModel($category_group, lang('command_category_groups_group_not_saved'));

        $category_group->save();
        $this->info(lang('command_category_groups_group_saved'));
    }
}
