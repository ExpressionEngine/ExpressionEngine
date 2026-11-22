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
 * Command to delete a category
 */
class CommandCategoriesDelete extends Cli
{
    use CliOptionsTrait;

    /**
     * name of command
     * @var string
     */
    public $name = 'Delete Category';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'categories:delete';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php categories:delete --cat_id=<cat_id> [--force]';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'cat_id,i:' => 'command_categories_category_id',
        'force,f' => 'command_sites_force_delete',
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        if ($this->option('--cat_id', false) === false) {
            $site_id = $this->getMsmSiteId('all');
        }

        $categories = [];
        $categoriesQuery = ee('Model')->get('Category')->with('CategoryGroup');
        if (isset($site_id) && !empty($site_id)) {
            $categoriesQuery->filter('site_id', $site_id);
        }
        foreach ($categoriesQuery->all() as $category) {
            $categories[$category->cat_id] = $category->cat_name . ' (' . $category->CategoryGroup->group_name . ')';
        }

        $this->data['cat_id'] = $this->getOptionValue('cat_id', [
            'type' => 'select',
            'desc' => 'command_categories_delete_ask',
            'choices' => $categories,
            'default' => null,
            'required' => true
        ]);

        if (!array_key_exists($this->data['cat_id'], $categories)) {
            $this->fail(lang('command_categories_not_found'));
        }

        $category = ee('Model')->get('Category', $this->data['cat_id'])->first();
        $confirmation = $this->option('--force', false);
        if (!$confirmation) {
            $confirmation = $this->confirm(sprintf(lang('command_categories_delete_confirm'), $category->cat_name), false);
        }
        if (!$confirmation) {
            $this->fail(lang('command_categories_not_deleted'));
        }

        $this->info(lang('command_categories_deleting_category'));

        $category->delete();

        $this->complete(lang('command_categories_deleted'));
    }
}
