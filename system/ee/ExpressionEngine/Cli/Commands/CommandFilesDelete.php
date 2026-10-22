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
 * Command to delete a file
 */
class CommandFilesDelete extends Cli
{
    use CliOptionsTrait;

    /**
     * name of command
     * @var string
     */
    public $name = 'Delete File';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'files:delete';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php files:delete --file_id=<file_id> [--force] [--delete_file]';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'file_id,i:' => 'command_files_file_id',
        'site,s:' => 'command_category_groups_list_option_site',
        'upload_id,u:' => 'command_files_upload_id',
        'force,f' => 'command_sites_force_delete'
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

        $confirmation = $this->option('--force', false);
        if (!$confirmation) {
            $confirmation = $this->confirm(sprintf(lang('command_files_delete_confirm'), $file->file_name), false);
        }
        if (!$confirmation) {
            $this->fail(lang('command_files_not_deleted'));
        }

        $this->info(lang('command_files_deleting'));

        $file->delete();
        $this->complete(lang('command_files_deleted'));
    }
}