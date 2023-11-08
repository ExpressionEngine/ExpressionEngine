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
 * Backs up and updates the ExpressionEngine install's files to the new,
 * downloaded version, and also provides rollback functionality for those files
 */
class FileUpdater
{
    protected $filesystem = null;
    protected $verifier = null;
    protected $logger = null;

    // Public for unit testing :/
    public $configs = [];

    public function __construct(Filesystem $filesystem, Verifier $verifier, Logger $logger)
    {
        $this->filesystem = $filesystem;
        $this->verifier = $verifier;
        $this->logger = $logger;
        $this->configs = $this->parseConfigs();
    }

    public function updateFiles()
    {
        try {
            $this->backupExistingInstallFiles();
        } catch (\Exception $e) {
            throw new UpdaterException(
                "There was a problem backing up your installation:\n\n" . $e->getMessage(),
                $e->getCode()
            );
        }

        try {
            $this->moveNewInstallFiles();
        } catch (\Exception $e) {
            throw new UpdaterException(
                "There was a problem moving over the new ExpressionEngine files:\n\n" . $e->getMessage(),
                $e->getCode()
            );
        }

        try {
            $this->verifyNewFiles();
        } catch (\Exception $e) {
            throw new UpdaterException(
                "There was a problem verifying the new ExpressionEngine files have been successfully put into place:\n\n" . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    /**
     * Backs up the existing install files
     */
    public function backupExistingInstallFiles()
    {
        $this->logger->log('Starting backup of existing installation');

        // First backup the contents of system/ee, excluding ourselves
        $this->move(
            SYSPATH . 'ee/',
            $this->getBackupsPath() . 'system_ee/',
            [SYSPATH . 'ee/updater']
        );

        // We'll only backup one theme folder, they _should_ all be the same
        // across sites
        $theme_path = array_values($this->configs['theme_paths'])[0];
        $theme_path = rtrim($theme_path, DIRECTORY_SEPARATOR) . '/ee/';

        $this->move($theme_path, $this->getBackupsPath() . 'themes_ee/');
    }

    /**
     * Backs up the existing install files
     */
    public function moveNewInstallFiles()
    {
        $this->logger->log('Moving new ExpressionEngine installation into place');

        // Move new system/ee folder contents into place
        $new_system_dir = $this->configs['archive_path'] . '/system/ee/';

        $this->move($new_system_dir, SYSPATH . 'ee/');

        // Now move new themes into place
        $new_themes_dir = $this->configs['archive_path'] . '/themes/ee/';

        // If multiple theme paths exist, _copy_ the themes to each folder
        if (count($this->configs['theme_paths']) > 1) {
            $this->logger->log('Multiple theme paths detected, copying new themes folders into place');

            foreach ($this->configs['theme_paths'] as $theme_path) {
                $theme_path = rtrim($theme_path, DIRECTORY_SEPARATOR) . '/ee/';

                $this->copy($new_themes_dir, $theme_path);
            }
        }
        // Otherwise, just move the themes to the one themes folder
        else {
            $theme_path = array_values($this->configs['theme_paths'])[0];
            $theme_path = rtrim($theme_path, DIRECTORY_SEPARATOR) . '/ee/';

            $this->move($new_themes_dir, $theme_path);
        }
    }

    /**
     * Verifies the newly-moved files made it over intact
     */
    public function verifyNewFiles()
    {
        $this->logger->log('Verifying the integrity of the new ExpressionEngine files');

        $this->verifyFiles(SYSPATH . 'ee/', 'system/ee');

        foreach ($this->configs['theme_paths'] as $theme_path) {
            $theme_path = rtrim($theme_path, DIRECTORY_SEPARATOR) . '/ee/';
            $this->verifyFiles($theme_path, 'themes/ee');
        }

        $this->logger->log('New ExpressionEngine files successfully verified');
    }

    /**
     * Verifies the newly-moved files made it over intact
     *
     * @param	string	$path		Local server path to verify
     * @param	string	$ref_path	Relative path in the manifest file to compare against
     */
    private function verifyFiles($path, $ref_path)
    {
        $this->verifier->verifyPath(
            $path,
            SYSPATH . 'ee/updater/hash-manifest',
            $ref_path,
            ['system/ee/installer/updater', 'system/eecli.php']
        );
    }

    /**
     * Rolls back to the previous installation's files and puts the new
     * installation's files back in the extracted archive path in case we need
     * to inspect them
     */
    public function rollbackFiles()
    {
        $this->logger->log('Rolling back to previous installation\'s files');

        // Move back the new installation
        $this->move(
            SYSPATH . 'ee/',
            $this->configs['archive_path'] . '/system/ee/',
            [SYSPATH . 'ee/updater']
        );

        // Now move new themes into place
        $new_themes_dir = $this->configs['archive_path'] . '/themes/ee/';

        // If multiple theme paths exist, delete the contents of them since we
        // copied to them before
        if (count($this->configs['theme_paths']) > 1) {
            foreach ($this->configs['theme_paths'] as $theme_path) {
                $theme_path = rtrim($theme_path, DIRECTORY_SEPARATOR) . '/ee/';

                $this->delete($theme_path);
            }
        }
        // Otherwise, move the themes folder back to the archive folder
        else {
            $theme_path = $this->configs['theme_paths'][0];
            $theme_path = rtrim($theme_path, DIRECTORY_SEPARATOR) . '/ee/';

            $this->move($theme_path, $new_themes_dir);
        }

        // Now, restore backups
        $this->move(
            $this->getBackupsPath() . 'system_ee/',
            SYSPATH . 'ee/'
        );

        // Copy themes backup to each theme folder (copy because there may be
        // multiple theme locations)
        foreach ($this->configs['theme_paths'] as $theme_path) {
            $theme_path = rtrim($theme_path, DIRECTORY_SEPARATOR) . '/ee/';

            $this->copy($this->getBackupsPath() . 'themes_ee/', $theme_path);
        }
    }

    /**
     * Moves contents of directories to another directory
     *
     * @param	string	$source			Source directory or file
     * @param	string	$destination	Destination directory
     * @param	array	$exclusions		Array of any paths to exlude when moving
     * @param	boolean	$copy			When TRUE, copies instead of moves
     */
    protected function move($source, $destination, array $exclusions = [], $copy = false)
    {
        if (! $this->filesystem->exists($destination)) {
            $this->filesystem->mkDir($destination, false);
        } elseif (! $this->filesystem->isDir($destination)) {
            throw new UpdaterException('Destination path not a directory: ' . $destination, 18);
        } elseif (! $this->filesystem->isWritable($destination)) {
            throw new UpdaterException('Destination path not writable: ' . $destination, 21);
        }

        if ($this->filesystem->isDir($source)) {
            $contents = $this->filesystem->getDirectoryContents($source);
        } else {
            //moving just one file
            $contents = [$source];
            $source = $this->filesystem->dirname($source);
        }

        $source = str_replace("\\", "/", $source);
        $destination = str_replace("\\", "/", $destination);
        $exclusions = array_map(function ($path) {
            return str_replace("\\", "/", $path);
        }, $exclusions);

        foreach ($contents as $path) {
            $path = str_replace("\\", "/", $path);

            // Skip exclusions and .DS_Store
            if (in_array($path, $exclusions) or strpos($path, '.DS_Store') !== false) {
                continue;
            }

            $new_path = str_replace($source, $destination, $path);
            $new_path = str_replace("//", "/", $new_path);

            // Try to catch permissions errors before PHP's file I/O functions do
            if (! $this->filesystem->isWritable($path)) {
                throw new UpdaterException("Cannot move {$path} to {$new_path}, path is not writable: {$path}", 19);
            }

            $this->logger->log('Moving ' . $path . ' to ' . $new_path);

            $method = $copy ? 'copy' : 'rename';
            $this->filesystem->$method($path, $new_path);
        }
    }

    /**
     * Copies contents of a directory to another directory
     *
     * @param	string	$source			Source directory
     * @param	string	$destination	Destination directory
     * @param	array	$exclusions		Array of any paths to exlude when moving
     */
    protected function copy($source, $destination, array $exclusions = [])
    {
        $this->move($source, $destination, $exclusions, true);
    }

    /**
     * Deletes contents of a directory
     *
     * @param	string	$directory	Direcotry to delete the contents from
     * @param	array	$exclusions	Array of any paths to exlude when deleting
     */
    protected function delete($directory, array $exclusions = [])
    {
        $contents = $this->filesystem->getDirectoryContents($directory);

        foreach ($contents as $path) {
            // Skip exclusions
            if (in_array($path, $exclusions)) {
                continue;
            }

            if (! $this->filesystem->isWritable($path)) {
                throw new UpdaterException("Cannot delete path {$path}, it is not writable", 20);
            }

            $this->logger->log('Deleting ' . $path);
            $this->filesystem->delete($path);
        }
    }

    /**
     * Constructs and returns the backup path
     *
     * @return	string	Path to backups folder
     */
    public function getBackupsPath()
    {
        return $this->path() . 'backups/';
    }

    /**
     * Optionally creates and returns the path in which we will be working with
     * our files
     *
     * @return	string	Path to folder in the cache folder for working with updates
     */
    public function path()
    {
        return PATH_CACHE . 'ee_update/';
    }

    /**
     * Opens the configs.json file and parses the JSON as an associative array
     *
     * @return	array	Associative array of configs
     */
    protected function parseConfigs()
    {
        $configs_path = $this->path() . 'configs.json';

        if (! $this->filesystem->exists($configs_path)) {
            throw new UpdaterException('Cannot find ' . $configs_path, 17);
        }

        return json_decode($this->filesystem->read($configs_path), true);
    }
}

// EOF
