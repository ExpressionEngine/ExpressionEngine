<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Service\Updater;

use ExpressionEngine\Updater\Service\Updater\Logger;
use ExpressionEngine\Updater\Service\Updater\UpdaterException;
use ExpressionEngine\Updater\Library\Filesystem\Filesystem;

/**
 * Legacy Files updater for one-click updater
 *
 * Copies over necessary legacy files when upgrading between major versions
 */
class LegacyFiles
{
    protected $from_version;
    protected $backup_path;
    protected $logger;

    /**
     * Construct class
     *
     * @param	string		$from_version	Version we are updating from
     * @param	Logger		$logger	Logger instance
     * @return  void
     */
    public function __construct($from_version, $backup_path, Logger $logger)
    {
        $this->from_version = $from_version;
        $this->backup_path = $backup_path;
        $this->logger = $logger;
    }

    /**
     * adds legacy files
     * @return array
     */
    public function addFiles()
    {
        $version = explode('.', $this->from_version, 2);
        $filesystem = new Filesystem();

        if ($filesystem->exists($this->backup_path . 'system_ee/EllisLab')) {
            $this->copyFiles(
                SYSPATH . 'ee/installer/files/ee5/',
                SYSPATH . 'ee/'
            );
        }
    }

    /**
     * Copy the files
     *
     * @param [type] $source
     * @param [type] $destination
     * @return void
     */
    private function copyFiles($source, $destination)
    {
        $filesystem = new Filesystem();
        if (! $filesystem->exists($destination)) {
            $filesystem->mkDir($destination, false);
        } elseif (! $filesystem->isDir($destination)) {
            throw new UpdaterException('Destination path not a directory: ' . $destination, 18);
        } elseif (! $filesystem->isWritable($destination)) {
            throw new UpdaterException('Destination path not writable: ' . $destination, 21);
        }

        $contents = $filesystem->getDirectoryContents($source);

        $source = str_replace("\\", "/", $source);
        $destination = str_replace("\\", "/", $destination);

        foreach ($contents as $path) {
            $path = str_replace("\\", "/", $path);
            $new_path = str_replace($source, $destination, $path);

            // Try to catch permissions errors before PHP's file I/O functions do
            if (!$filesystem->isWritable($path)) {
                throw new UpdaterException("Cannot move {$path} to {$new_path}, path is not writable: {$path}", 19);
            }

            $this->logger->log('Moving ' . $path . ' to ' . $new_path);

            $filesystem->copy($path, $new_path);
        }
    }
}
