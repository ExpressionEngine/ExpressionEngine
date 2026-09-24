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
 * Command to add a new file
 */
class CommandFilesAdd extends Cli
{
    use CliOptionsTrait;

    /**
     * name of command
     * @var string
     */
    public $name = 'Add File';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'files:add';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php files:add [--upload_id=<upload_id>] [--file_path="path/to/file"] [--source_path="path/to/source"]';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'upload_id,i:' => 'command_files_upload_id',
        'site,s:' => 'command_msm_site_id',
        'source:' => 'command_files_add_source',
        'existing:' => 'command_files_file_exists',
        'new_name:' => 'command_files_new_file_name',
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        ee()->lang->loadfile('filemanager');

        if ($this->option('--upload_id', false) !== false) {
            $uploadId = $this->option('--upload_id');
        } else {
            $site_id = $this->getMsmSiteId('all');

            $uploadDirectories = ee('Model')->get('UploadDestination')->fields('id', 'name')->filter('module_id', '')->filter('adapter', 'local');
            if (isset($site_id) && !is_null($site_id)) {
                $uploadDirectories->filter('site_id', $site_id);
            }
            $uploadDirectories = $uploadDirectories->all()->getDictionary('id', 'name');
            $uploadId = $this->getOptionValue('upload_id', [
                'type' => 'select',
                'desc' => 'command_files_upload_id',
                'choices' => $uploadDirectories,
                'default' => '',
                'required' => true
            ]);
        }

        $uploadDirectory = ee('Model')->get('UploadDestination', $uploadId)->first();

        if (empty($uploadDirectory)) {
            $this->fail(lang('command_files_upload_not_found'));
        }

        $fileSource = $this->getOptionValue('source', [
            'type' => 'select',
            'desc' => 'command_files_add_source',
            'choices' => [
                'relative' => lang('command_files_source_relative'),
                'absolute' => lang('command_files_source_absolute'),
            ],
            'default' => 'relative',
            'required' => true,
        ]);

        $path = $this->getOptionOrAsk(
            '--path',
            ($fileSource === 'relative') ? lang('command_files_file_path') : lang('command_files_source_path'),
            '',
            true
        );

        $this->info(lang('command_files_adding'));

        if ($fileSource === 'absolute') {
            try {
                $originalPath = $path;
                $path = basename($path);
                if ($uploadDirectory->getFilesystem()->exists($uploadDirectory->server_path . '/' . $path)) {
                    $existing = $this->getOptionValue('existing', [
                        'type' => 'select',
                        'desc' => 'command_files_file_exists',
                        'choices' => [
                            'skip' => lang('command_files_file_exists_skip'),
                            'overwrite' => lang('command_files_file_exists_overwrite'),
                            'rename' => lang('command_files_file_exists_rename'),
                        ],
                        'default' => 'skip',
                        'required' => true,
                    ]);
                    if ($existing === 'skip') {
                        $this->fail(lang('command_files_not_added'));
                    } elseif ($existing === 'rename') {
                        while ($uploadDirectory->getFilesystem()->exists($uploadDirectory->server_path . '/' . $path)) {
                            $path = ee('Security/XSS')->clean(pathinfo($path, PATHINFO_FILENAME) . '_1.' . pathinfo($path, PATHINFO_EXTENSION));
                        }
                        $path = $this->getOptionOrAsk(
                            '--new_name',
                            lang('command_files_new_file_name'),
                            $path,
                            true
                        );
                    } else {
                        // overwrite, do nothing special
                    }
                }
                $uploadDirectory->getFilesystem()->forceCopy($originalPath, $uploadDirectory->server_path . '/' . $path);
            } catch (\Exception $e) {
                $this->fail($e->getMessage());
            }
        }

        try {
            $uploaded = $uploadDirectory->syncFiles([$path]);
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }
        if ($uploaded !== true) {
            foreach ($uploaded as $message) {
                $this->info($message);
            }
            $this->fail(lang('command_files_not_added'));
        }
        $this->complete(lang('command_files_added'));
    }
}