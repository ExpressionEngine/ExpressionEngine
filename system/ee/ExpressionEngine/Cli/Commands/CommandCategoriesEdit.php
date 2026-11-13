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
 * Command to edit category
 */
class CommandCategoriesEdit extends Cli
{
    use CliOptionsTrait;

    /**
     * name of command
     * @var string
     */
    public $name = 'Edit Category';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'categories:edit';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php categories:edit [--cat_id=<category_id>]';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'cat_id,i:' => 'command_categories_category_id',
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

        if ($this->option('--cat_id', false) === false) {
            $site_id = $this->getMsmSiteId('all');
        }

        $categories = [];
        $categoriesQuery = ee('Model')->get('Category')->with('CategoryGroup');
        if (isset($site_id) && !empty($site_id)) {
            $categoriesQuery->filter('site_id', $site_id);
        }
        $categoriesQuery = $categoriesQuery->all();
        foreach ($categoriesQuery as $category) {
            $categories[$category->cat_id] = $category->cat_name . ' (' . $category->CategoryGroup->group_name . ')';
        }

        $this->data['cat_id'] = $this->getOptionValue('cat_id', [
            'type' => 'select',
            'desc' => 'command_categories_edit_ask',
            'choices' => $categories,
            'default' => null,
            'required' => true
        ]);

        if (!array_key_exists($this->data['cat_id'], $categories)) {
            $this->fail(lang('command_categories_not_found'));
        }

        $category = $categoriesQuery->filter('cat_id', $this->data['cat_id'])->first();

        $this->info(sprintf(lang('command_categories_editing'), $category->cat_name));

        $category = $this->getFieldsForCategories($category);

        $category->set($this->data);

        if (isset($this->data['group_id'])) {
            $categoryGroup = ee('Model')->get('CategoryGroup', $this->data['group_id'])->first();
            if (empty($categoryGroup)) {
                $this->fail(lang('command_category_groups_not_found'));
            }
        }

        $this->validateModel($category, lang('command_categories_not_saved'));

        $category->save();
        $this->complete(lang('command_categories_saved'));
    }

}
