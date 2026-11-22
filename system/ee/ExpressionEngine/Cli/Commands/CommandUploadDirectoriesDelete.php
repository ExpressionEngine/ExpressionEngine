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
use ExpressionEngine\Service\Model\Collection;
use ExpressionEngine\Cli\CliOptionsTrait;

/**
 * Command to delete an upload directory
 */
class CommandUploadDirectoriesDelete extends Cli
{
    use CliOptionsTrait;

    /**
     * name of command
     * @var string
     */
    public $name = 'Delete Upload Directory';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'upload-directories:delete';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php upload-directories:delete --upload_id=<upload_id> [--force]';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'upload_id,u:' => 'command_upload_directories_upload_id',
        'force,f' => 'command_sites_force_delete',
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $site_id = null;
        if ($this->option('--upload_id', false) === false) {
            $site_id = $this->getMsmSiteId('all');
        }

        $uploadQuery = ee('Model')->get('UploadDestination')->filter('module_id', '');

        if (isset($site_id) && !empty($site_id)) {
            $uploadQuery->filter('site_id', 'IN', [0, $site_id]);
        }

        $uploadDirectories = $uploadQuery->all()->getDictionary('id', 'name');

        $this->data['id'] = $this->getOptionValue('upload_id', [
            'type' => 'select',
            'desc' => 'command_upload_directories_delete_ask',
            'choices' => $uploadDirectories,
            'default' => null,
            'required' => true
        ]);

        if (!array_key_exists($this->data['id'], $uploadDirectories)) {
            $this->fail(lang('command_upload_directories_not_found'));
        }

        $uploadDirectory = ee('Model')->get('UploadDestination', $this->data['id'])->first();

        $confirmation = $this->option('--force', false);
        if (!$confirmation) {
            $confirmation = $this->confirm(sprintf(lang('command_upload_directories_delete_confirm'), $uploadDirectory->name), false);
        }
        if (!$confirmation) {
            $this->fail(lang('command_upload_directories_not_deleted'));
        }

        $this->info(lang('command_upload_directories_deleting'));

        $uploadDirectory->delete();

        $this->complete(lang('command_upload_directories_deleted'));
    }
}