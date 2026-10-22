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
 * Command to edit an upload directory
 */
class CommandUploadDirectoriesEdit extends Cli
{
    use CliOptionsTrait;

    /**
     * name of command
     * @var string
     */
    public $name = 'Edit Upload Directory';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'upload-directories:edit';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php upload-directories:edit [--upload_id=<upload_id>]';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'upload_id,u:' => 'command_upload_directories_upload_id',
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
        ee()->lang->loadfile('core');
        ee()->lang->loadfile('filemanager');
        ee()->lang->loadfile('content');

        $uploadDirectories = [];

        if ($this->option('--upload_id', false) === false) {
            $uploadQuery = ee('Model')->get('UploadDestination')->filter('module_id', '')->order('name', 'asc');
            foreach ($uploadQuery->all() as $directory) {
                $uploadDirectories[$directory->id] = $directory->name;
            }
        }

        $this->data['id'] = $this->getOptionValue('upload_id', [
            'type' => 'select',
            'desc' => 'command_upload_directories_edit_ask',
            'choices' => $uploadDirectories,
            'default' => null,
            'required' => true,
        ]);

        $uploadDirectory = ee('Model')->get('UploadDestination', $this->data['id'])->with('Roles', 'CategoryGroups')->all()->first();
        if (empty($uploadDirectory)) {
            $this->fail(lang('command_upload_directories_not_found'));
        }

        $this->info(sprintf(lang('command_upload_directories_editing'), $uploadDirectory->name));

        $uploadDirectory = $this->getFieldsForUploadDirectories($uploadDirectory);

        $uploadDirectory->set($this->data);
        $this->validateModel($uploadDirectory, lang('command_upload_directories_not_saved'));

        $uploadDirectory->save();
        $this->complete(lang('command_upload_directories_saved'));
    }
}