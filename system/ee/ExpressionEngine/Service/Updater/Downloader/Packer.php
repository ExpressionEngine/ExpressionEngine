<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Updater\Downloader;

use ExpressionEngine\Service\Updater\Downloader\UpdaterPaths;
use ExpressionEngine\Service\Updater\UpdaterException;
use ExpressionEngine\Service\Updater\Verifier;
use ExpressionEngine\Service\Updater\Logger;
use ExpressionEngine\Service\Updater\RequirementsCheckerLoader;
use ExpressionEngine\Library\Filesystem\Filesystem;
use ZipArchive;
use FilesystemIterator;

/**
 * Updater unpacker
 *
 * Unpacks the downloaded ExpressionEngine zip archive, verifies the integrity
 * of the files, checks the downloaded installation's server requirements, and
 * finally moves the micro app into place to facilitate the rest of the upgrade
 */
class Packer
{
    use UpdaterPaths;

    protected $filesystem;
    protected $zip_archive;

    protected $manifest_location = 'system/ee/installer/updater/hash-manifest';

    /**
     * Constructor
     *
     * @param Filesystem $filesystem Filesystem service object
     * @param ZipArchive $zip_archive PHP-native ZipArchive object
     */
    public function __construct(Filesystem $filesystem, ZipArchive $zip_archive)
    {
        $this->filesystem = $filesystem;
        $this->zip_archive = $zip_archive;
    }

    /**
     * Pack and copy the addon folder
     *
     * @param string $addonShortName Short name of the addon
     * @return string Result message
     */
    public function packAddon($addonShortName)
    {
        $destinationPath = $this->path() . $addonShortName;

        // if the folder already exists, remove it
        if (ee('Filesystem')->exists($destinationPath)) {
            ee('Filesystem')->deleteDir($destinationPath);
        }
        try {
            ee('Filesystem')->copy(PATH_THIRD . $addonShortName, $destinationPath . '/system/user/addons/' . $addonShortName);
        } catch (\Exception $e) {
            throw new \Exception(lang('addons_pack_copy_failed'));
        }

        if (ee('Filesystem')->exists(PATH_THIRD_THEMES . $addonShortName)) {
            try {
                ee('Filesystem')->copy(PATH_THIRD_THEMES . $addonShortName, $destinationPath . '/themes/user/' . $addonShortName);
            } catch (\Exception $e) {
                throw new \Exception(lang('addons_pack_copy_failed'));
            }
        }

        if (ee('Filesystem')->exists($this->getArchiveFilePath())) {
            ee('Filesystem')->rename($this->getArchiveFilePath(), $this->path() . $addonShortName . '_'. ee()->localize->now . '.zip');
        }

        $this->zip_archive->open($this->getArchiveFilePath(), ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // Create recursive directory iterator
        // Skip parent and root directories ( "." and ".." )
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($destinationPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            // Get real and relative path for current file
            $filePath = $file->getRealPath();

            // Calculate the relative path within the zip archive
            // The +1 is to remove the leading slash from the relative path if the source path is absolute
            $relativePath = substr($filePath, strlen($destinationPath) + 1);

            if (!$file->isDir()) {
                // Add current file to archive
                $this->zip_archive->addFile($filePath, $relativePath);
            } else {
                // Add empty directory to archive (only if it's an actual directory and not just a file path component)
                // Note: addFile() automatically creates parent directories when adding a file path
                if ($relativePath !== false) {
                    $this->zip_archive->addEmptyDir($relativePath);
                }
            }
        }

        $this->zip_archive->close();

        ee('Filesystem')->deleteDir($this->getExtractedArchivePath());

        $addon = ee('pro:Addon')->get($addonShortName);
        $resultMessage = sprintf(lang('addons_pack_complete'), $addon->getName());

        return $resultMessage;
    }
}

// EOF
