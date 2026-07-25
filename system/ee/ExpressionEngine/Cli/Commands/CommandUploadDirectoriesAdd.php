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
 * Command to add a new upload directory
 */
class CommandUploadDirectoriesAdd extends Cli
{
    use CliOptionsTrait;

    /**
     * name of command
     * @var string
     */
    public $name = 'Add Upload Directory';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'upload-directories:add';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php upload-directories:add [--site=<site_id>] [--name="directory_name"]';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'site,s:' => 'command_upload_directories_site_id',
        'name,n:' => 'command_upload_directories_name',
        'adapter:' => 'command_upload_directories_adapter',
        'allow_subfolders' => 'allow_subfolders'
    ];

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

        $this->data['site_id'] = (int) $this->getMsmSiteId('first', true);

        $this->info(lang('command_upload_directories_adding'));

        $uploadDirectory = $this->getFieldsForUploadDirectories();
        $uploadDirectory->set($this->data);

        $this->validateModel($uploadDirectory, lang('command_upload_directories_not_added'));

        $uploadDirectory->save();
        $this->complete(lang('command_upload_directories_added'));
    }
}