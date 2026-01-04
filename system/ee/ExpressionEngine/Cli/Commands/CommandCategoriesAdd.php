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
 * Command to add a new category
 */
class CommandCategoriesAdd extends Cli
{
    use CliOptionsTrait;

    /**
     * name of command
     * @var string
     */
    public $name = 'Add Category';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'categories:add';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php categories:add [--category_group=<category_group_id>] [--name="category_name"]';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'site,s:' => 'command_category_groups_list_option_site',
        'category_group,g:' => 'command_categories_category_group',
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
        ee()->lang->load('admin_content');
        ee()->lang->load('channel');

        $site_id = $this->getMsmSiteId('first');

        $category_groups = ee('Model')->get('CategoryGroup')->fields('group_id', 'group_name');
        if (isset($site_id) && !is_null($site_id)) {
            $category_groups->filter('site_id', $site_id);
        }
        $category_groups = $category_groups->all()->getDictionary('group_id', 'group_name');
        if (empty($category_groups)) {
            $this->fail(lang('command_category_groups_not_found'));
        }
        $this->data['group_id'] = $this->getOptionValue('category_group', [
            'type' => 'select',
            'desc' => 'category_group',
            'choices' => $category_groups,
            'default' => null,
            'required' => true
        ]);

        if (empty($this->data['group_id']) || !array_key_exists($this->data['group_id'], $category_groups)) {
            $this->fail(lang('command_category_groups_not_found'));
        }

        $this->data['site_id'] = ee('Model')->get('CategoryGroup', $this->data['group_id'])->first()->site_id;

        $this->info(lang('command_categories_adding'));

        $this->getFieldsForCategories();

        $category = ee('Model')->make('Category', $this->data);
        $this->validateModel($category, lang('command_categories_not_added'));

        $category->save();
        $this->complete(lang('command_categories_added'));
    }

}
