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
 * Command to delete a category group
 */
class CommandCategoryGroupDelete extends Cli
{
    use CliOptionsTrait;

    /**
     * name of command
     * @var string
     */
    public $name = 'Delete Category Group';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'cgroups:delete';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php cgroups:delete --group_id=<group_id> [--force]';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'group_id,i:' => 'command_category_groups_id',
        'force,f' => 'command_sites_force_delete',
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        if ($this->option('--group_id', false) === false) {
            $site_id = $this->getMsmSiteId('all');
        }
        $category_groups = ee('Model')->get('CategoryGroup');
        if (isset($site_id) && !empty($site_id)) {
            $category_groups->filter('site_id', $site_id);
        }
        $category_groups = $category_groups->all()->getDictionary('group_id', 'group_name');

        $this->data['group_id'] = $this->getOptionValue('group_id', [
            'type' => 'select',
            'desc' => 'command_category_groups_delete_ask',
            'choices' => $category_groups,
            'default' => null,
            'required' => true
        ]);

        if (!array_key_exists($this->data['group_id'], $category_groups)) {
            $this->fail(lang('command_category_groups_not_found'));
        }

        $category_group = ee('Model')->get('CategoryGroup', $this->data['group_id'])->first();

        $confirmation = $this->option('--force', false);
        if (!$confirmation) {
            $confirmation = $this->confirm(sprintf(lang('command_category_groups_delete_confirm'), $category_group->group_name), false);
        }
        if (!$confirmation) {
            $this->fail(lang('command_category_groups_group_not_deleted'));
        }

        $this->info(lang('command_category_groups_deleting_group'));

        $category_group->delete();

        $this->complete(lang('command_category_groups_group_deleted'));
    }
}
