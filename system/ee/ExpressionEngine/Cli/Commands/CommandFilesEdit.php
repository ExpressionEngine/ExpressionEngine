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

        $title = $this->getOptionOrAsk('--title', lang('command_files_title'), $file->title, false);
        if (!empty($title)) {
            $this->data['title'] = $title;
        }

        $description = $this->getOptionOrAsk('--description', lang('command_files_description'), $file->description, false);
        if (!empty($description)) {
            $this->data['description'] = $description;
        }

        $file->set($this->data);
        $this->validateModel($file, lang('command_files_not_saved'));

        $file->save();
        $this->complete(lang('command_files_saved'));
    }
}