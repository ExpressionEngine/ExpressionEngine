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

/**
 * Sync upload directory
 */
class CommandSyncUploadDirectory extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Sync Upload Directory';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'sync:upload-directory';

    /**
     * description of command
     * @var string
     */
    public $description = 'command_sync_upload_directory_description';

    /**
     * summary of command
     * @var string
     */
    public $summary = 'command_sync_upload_directory_summary';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php sync:upload-directory';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'upload-id,u:'        => 'command_sync_upload_directory_option_id',
        'manipulations,m:'     => 'command_sync_upload_directory_option_regenerate_manipulations',
    ];
    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        ee()->lang->loadfile('utilities');
        ee()->lang->loadfile('filemanager');
        ee()->load->library('session');

        $this->info('command_sync_upload_directory_description');

        // Is this MSM installation?
        $siteLabels = [];
        $sites = ee('Model')->get('Site')->all();
        foreach ($sites as $site) {
            $siteLabels[$site->getId()] = '';
            if (count($sites) > 1) {
                $siteLabels[$site->getId()] = ' [' . $site->site_label . ']';
            }
        }
        $directories = ee('Model')->get('UploadDestination')->order('site_id', 'ASC')->order('id', 'ASC')->all();
        $directoriesList = "\n";
        foreach ($directories as $directory) {
            $directoriesList .= $directory->getId() . ' - ' . $directory->name . $siteLabels[$directory->site_id] . "\n";
        }
        $upload_location_id = $this->getOptionOrAsk('--upload-id', lang('command_sync_upload_directory_ask_id') . $directoriesList, '', true);

        $uploadLocation = ee('Model')->get('UploadDestination', $upload_location_id)->first();
        if (empty($uploadLocation)) {
            $this->fail('invalid_upload_destination');
        }

        $fileDimensions = ee('Model')->get('FileDimension')->filter('upload_location_id', $upload_location_id)->all();
        $manipulations = '';
        if (!empty($fileDimensions) && $fileDimensions->count() > 0) {
            $manipulationsList = "\n";
            foreach ($fileDimensions as $fileDimension) {
                $manipulationsList .= $fileDimension->getId() . ' - ' . $fileDimension->short_name . ' [' . lang($fileDimension->resize_type) . ', ' . $fileDimension->width . 'px ' . lang('by') . ' ' . $fileDimension->height . 'px' . "]\n";
            }
            $manipulations = $this->getOptionOrAsk('--manipulations', lang('command_sync_upload_directory_ask_regenerate_manipulations') . $manipulationsList, '');
        }

        $replaceSizeIds = [];
        if (trim($manipulations) == 'all') {
            $replaceSizeIds = $fileDimensions->pluck('id');
        } else {
            $replaceSizeIds = array_map('trim', explode(',', $manipulations));
        }

        if ($uploadLocation->adapter == 'local' && is_object($uploadLocation->getRawProperty('server_path')) && strpos($uploadLocation->getRawProperty('server_path')->path, '{base_path}') !== false && empty(ee()->config->item('base_path'))) {
            $this->fail('cli_error_sync_upload_directory_base_path_is_empty');
        }

        if (! $uploadLocation->exists()) {
            $this->fail(strip_tags(sprintf(lang('directory_not_found'), addslashes($uploadLocation->server_path))));
        }

        // Get a listing of raw files in the directory
        $filesChunks = array_chunk($uploadLocation->getAllFileNames(), 5);

        $allSizes = array();
        foreach ($fileDimensions as $size) {
            $allSizes[$size->upload_location_id][$size->id] = array(
                'short_name' => $size->short_name,
                'resize_type' => $size->resize_type,
                'width' => $size->width,
                'height' => $size->height,
                'quality' => $size->quality,
                'watermark_id' => $size->watermark_id
            );
        }

        $errors = [];
        $this->write('command_sync_upload_directory_started');
        foreach ($filesChunks as $files) {
            $synced = $uploadLocation->syncFiles($files, $allSizes, $replaceSizeIds);
            if ($synced !== true) {
                $errors = array_merge($errors, $synced);
            } else {
                echo '.';
            }
        }

        $this->write('');

        if (!empty($errors)) {
            $this->info('directory_sync_warning');
            $this->write('');
            foreach ($errors as $file => $error) {
                $this->write($file . ': ' . $error);
            }
            $this->fail();
        }

        $this->info('directory_synced_desc');
    }
}
