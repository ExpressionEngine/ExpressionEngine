<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP\FileManager\Traits;

trait FileUsageTrait
{
    protected static function getFileUsageReplacements($data = '')
    {
        if (bool_config_item('file_manager_compatibility_mode') || empty($data)) {
            return [];
        }
        if (strpos((string) $data, '{filedir_') === false) {
            return [];
        }

        $fileUsageReplacements = [];
        $dirsAndFiles = [];
        $dirsAndFilesInSubfolders = [];
        if (preg_match_all('/{filedir_(\d+)}([^\"\'\s]*)/', $data, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                if (strpos($match[2], '/', 1) !== false) {
                    $dirsAndFilesInSubfolders[$match[1]][] = $match[2];
                } else {
                    $dirsAndFiles[$match[1]][] = $match[2];
                }
            }
        }
        if (!empty($dirsAndFiles)) {
            $files = ee('Model')
                ->get('File')
                ->fields('file_id', 'upload_location_id', 'file_name');
            $files->filterGroup();
            foreach ($dirsAndFiles as $dir_id => $file_names) {
                $files->orFilterGroup()
                    ->filter('upload_location_id', $dir_id)
                    ->filter('file_name', 'IN', $file_names)
                    ->endFilterGroup();
            }
            $files->endFilterGroup();
            foreach ($files->all() as $file) {
                $fileUsageReplacements[$file->getId()] = ['{filedir_' . $file->upload_location_id . '}' . $file->file_name => '{file:' . $file->file_id . ':url}'];
            }
        }
        if (!empty($dirsAndFilesInSubfolders)) {
            foreach ($dirsAndFilesInSubfolders as $dir_id => $file_names) {
                $uploadLocation = ee('Model')->get('UploadDestination', $dir_id)->with('FileDimensions')->first(true);
                $dimensions = $uploadLocation->FileDimensions->pluck('short_name');
                $dimensions[] = 'thumbs';
                // Make sure UploadLocation still exists, would be better if we could filter out these files earlier
                if (!is_null($uploadLocation)) {
                    foreach ($file_names as $i => $fileRealtivePath) {
                        $modifier = 'url';
                        // is this a manipulated image?
                        if (!empty($dimensions)) {
                            $fileRealtivePathParts = explode('/', $fileRealtivePath);
                            $filename = end($fileRealtivePathParts);
                            $possibleDimension = substr(prev($fileRealtivePathParts), 1); // strip _ from the beginning
                            if (in_array($possibleDimension, $dimensions)) {
                                $modifier = $possibleDimension;
                                // remove the dimension from the path, so we could get file by path
                                $fileRealtivePath = str_replace('/' . $possibleDimension . '/' . $filename, '/' . $filename, $fileRealtivePath);
                            }
                        }
                        $file = $uploadLocation->getFileByPath($fileRealtivePath);
                        if (!empty($file)) {
                            $fileUsageReplacements[$file->getId()] = ['{filedir_' . $file->upload_location_id . '}' . $fileRealtivePath => '{file:' . $file->file_id . ':' . $modifier . '}'];
                        }
                    }
                }
            }
        }

        return $fileUsageReplacements;
    }
}
