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
 * Command to edit a file
 */
class CommandFilesEdit extends Cli
{
    use CliOptionsTrait;

    /**
     * name of command
     * @var string
     */
    public $name = 'Edit File';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'files:edit';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php files:edit [--file_id=<file_id>]';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'file_id,f:' => 'command_files_file_id',
        'title:' => 'command_files_title',
        'description:' => 'command_files_description',
        'site,s:' => 'command_msm_site_id',
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        ee()->lang->loadfile('filemanager');

        if ($this->option('--file_id', false) === false) {
            if ($this->option('--upload_id', false) !== false) {
                $uploadId = $this->option('--upload_id');
            } else {
                $site_id = $this->getMsmSiteId('all');

                $uploadDirectories = ee('Model')->get('UploadDestination')->fields('id', 'name')->filter('module_id', '');
                if (isset($site_id) && !is_null($site_id)) {
                    $uploadDirectories->filter('site_id', $site_id);
                }
                $uploadDirectories = ['all' => 'All Directories'] + $uploadDirectories->all()->getDictionary('id', 'name');
                $uploadId = $this->getOptionValue('upload_id', [
                    'type' => 'select',
                    'desc' => 'command_files_upload_id',
                    'choices' => $uploadDirectories,
                    'default' => 'all',
                    'required' => false
                ]);
            }
            $filesQuery = ee('Model')->get('File')->fields('file_id', 'file_name')->order('file_name', 'asc');
            if (isset($uploadId) && $uploadId !== 'all') {
                $filesQuery->filter('upload_location_id', $uploadId);
            }
            if (isset($site_id) && !is_null($site_id)) {
                $filesQuery->filter('site_id', 'IN', [0, $site_id]);
            }
            $files = [];
            foreach ($filesQuery->all() as $file) {
                $files[$file->file_id] = $file->file_name;
            }

            $this->data['file_id'] = $this->getOptionValue('file_id', [
                'type' => 'select',
                'desc' => 'command_files_delete_ask',
                'choices' => $files,
                'default' => null,
                'required' => true
            ]);
        } else {
            $this->data['file_id'] = $this->option('--file_id');
        }

        $file = ee('Model')->get('File', $this->data['file_id'])->first();

        if (is_null($file)) {
            $this->fail(lang('command_files_not_found'));
        }

        $this->info(sprintf(lang('command_files_editing'), $file->file_name));

        $this->data['title'] = $this->getOptionOrAsk('--title', lang('title'), $file->title, false);

        $this->data['description'] = $this->getOptionOrAsk('--description', lang('description'), $file->description, false);

        $this->data['credit'] = $this->getOptionOrAsk('--credit', lang('credit'), $file->credit, false);

        $this->data['location'] = $this->getOptionOrAsk('--location', lang('location'), $file->location, false);

        $this->data['modified_date'] = ee()->localize->now;
        $this->data['modified_by_member_id'] = ee('Member')->getDefaultCLIAuthor()->member_id;

        $file->set($this->data);

        // Get category group from upload directory and allow editing if available
        $uploadDirectory = $file->UploadDestination;
        if ($uploadDirectory && $uploadDirectory->CategoryGroups->count() > 0) {
            $categoryGroupIds = $uploadDirectory->CategoryGroups->pluck('group_id');
            if (!empty($categoryGroupIds)) {
                $categories = ee('Model')->get('Category')
                    ->filter('group_id', 'IN', $categoryGroupIds)
                    ->order('group_id', 'asc')
                    ->order('cat_name', 'asc')
                    ->all()
                    ->getDictionary('cat_id', 'cat_name');

                if (!empty($categories)) {
                    $currentCategories = $file->Categories->pluck('cat_id');
                    $selectedCategories = $this->getOptionValue('categories', [
                        'type' => 'checkbox',
                        'desc' => 'categories',
                        'choices' => $categories,
                        'default' => implode(',', $currentCategories),
                        'required' => false
                    ]);

                    $file->Categories = ee('Model')->get('Category', $selectedCategories)->all();
                }
            }
        }

        $this->validateModel($file, lang('command_files_not_saved'));

        $file->save();
        $this->complete(lang('command_files_saved'));
    }
}