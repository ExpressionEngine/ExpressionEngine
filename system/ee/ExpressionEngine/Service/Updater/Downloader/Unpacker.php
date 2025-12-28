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
class Unpacker
{
    use UpdaterPaths;

    protected $filesystem;
    protected $zip_archive;
    protected $verifier;
    protected $logger;
    protected $requirements;

    protected $manifest_location = 'system/ee/installer/updater/hash-manifest';

    protected $addonZipFolders = [];
    protected $addonFolders = [];
    protected $themesFolders = [];

    /**
     * Constructor
     *
     * @param   Filesystem $filesystem Filesystem service object
     * @param   ZipArchive $zip_archive PHP-native ZipArchive object
     * @param   Verifier $verifier File verifier object
     * @param   Logger $logger Updater logger object
     * @param   RequirementsCheckerLoader $requirements Requirements checker loader object
     */
    public function __construct(Filesystem $filesystem, ZipArchive $zip_archive, Verifier $verifier, Logger $logger, RequirementsCheckerLoader $requirements)
    {
        $this->filesystem = $filesystem;
        $this->zip_archive = $zip_archive;
        $this->verifier = $verifier;
        $this->logger = $logger;
        $this->requirements = $requirements;
    }

    /**
     * Unzips package to extract folder
     */
    public function unzipPackage()
    {
        $this->logger->log('Unzipping package');

        $this->filesystem->mkDir($this->getExtractedArchivePath());

        if (($response = $this->zip_archive->open($this->getArchiveFilePath())) === true) {
            $this->zip_archive->extractTo($this->getExtractedArchivePath());
            $this->zip_archive->close();
            $this->logger->log('Package unzipped');
        } else {
            throw new UpdaterException(
                sprintf(
                    lang('could_not_unzip') . "\n\n" . lang('try_again_later'),
                    $response
                ),
                8
            );
        }
    }

    /**
     * Goes through each file in the extracted archive to verify unzip integrity
     */
    public function verifyExtractedPackage()
    {
        $this->logger->log('Verifying integrity of unzipped package');

        $extracted_path = $this->getExtractedArchivePath();

        try {
            $this->verifier->verifyPath(
                $extracted_path,
                $extracted_path . '/' . $this->manifest_location
            );
        } catch (\Exception $e) {
            throw new UpdaterException(
                sprintf(lang('failed_verifying_extracted_archive'), $e->getMessage()) . "\n\n" . lang('try_again_later'),
                $e->getCode()
            );
        }

        $this->logger->log('Package contents successfully verified');
    }

    /**
     * Check server requirements for the new update before we bother doing anything else
     */
    public function checkRequirements()
    {
        $this->logger->log('Checking server requirements of new ExpressionEngine version');

        $el_req_path = $this->getExtractedArchivePath() . '/system/ee/installer/updater/EllisLab/ExpressionEngine/Updater/Service/Updater/RequirementsChecker.php';
        $ee_req_path = $this->getExtractedArchivePath() . '/system/ee/installer/updater/ExpressionEngine/Updater/Service/Updater/RequirementsChecker.php';

        // Check to see if we're dealing with the update from 5 to 6 that removed the EllisLab namespace.
        if (file_exists($el_req_path)) {
            $this->requirements->setClassPath($el_req_path);
        } else {
            $this->requirements->setClassPath($ee_req_path);
        }

        $result = $this->requirements->check();

        if ($result !== true) {
            $failed = array_map(function ($requirement) {
                return $requirement->getMessage();
            }, $result);

            throw new UpdaterException(
                sprintf(lang('requirements_failed'), implode("\n- ", $failed)),
                14
            );
        }

        $this->logger->log('Server requirements check passed with flying colors');
    }

    /**
     * Moves the update package into position to be executed and finish the upgrade
     */
    public function moveUpdater()
    {
        $this->logger->log('Moving the updater micro app into place');

        $source = $this->getExtractedArchivePath() . '/system/ee/installer/updater';

        // Check to see if the updater directory already exists
        if ($this->filesystem->exists(SYSPATH . 'ee/updater')) {
            $this->filesystem->deleteDir(SYSPATH . 'ee/updater');
        }

        $this->filesystem->rename($source, SYSPATH . 'ee/updater');

        try {
            $this->verifier->verifyPath(
                SYSPATH . '/ee/updater',
                SYSPATH . '/ee/updater/hash-manifest',
                'system/ee/installer/updater'
            );
        } catch (\Exception $e) {
            // Remove the updater
            $this->filesystem->deleteDir(SYSPATH . 'ee/updater');

            throw new UpdaterException(
                sprintf(lang('failed_moving_updater'), $e->getMessage()) . "\n\n" . lang('try_again_later'),
                $e->getCode()
            );
        }
    }

    /**
     * Get the list of add-on zip files in folder
     *
     * @return array List of add-on short names
     */
    public function getAddonsList()
    {
        $addons = [];
        $path = $this->path('addons');
        $files = new \FilesystemIterator($path, \FilesystemIterator::UNIX_PATHS);
        foreach ($files as $item) {
            if ($item->getExtension() == 'zip') {
                $addons[] = $item->getBasename('.zip');
            }
        }
        return $addons;
    }

    /**
     * Unpack and move the addon to the correct location
     *
     * @param string $addonShortName Short name of the addon
     * @return string Result message
     */
    public function unpackAndMoveAddon($addonShortName, $deleteZip = false)
    {
        // check if it has correct folder structure
        // several options here:
        // - the files are directly in the extracted folder
        // - there is a single folder inside the extracted folder that contains the files, matching the addon name
        // - there is system/user/addons structure inside the extracted folder
        $extracted = $this->getExtractedArchivePath();

        $this->iterateForAddon($extracted, $addonShortName);

        if (!array_key_exists($addonShortName, $this->addonFolders)) {
            $addonShortName = array_key_first($this->addonFolders);
        }

        if (empty($this->addonFolders)) {
            ee('Filesystem')->deleteDir($this->getExtractedArchivePath());
            throw new \Exception(lang('addons_unpack_invalid_structure'));
        }

        $failedToMove = [];
        $backupFolderId = uniqid();
        // copy everything we have to addons folder
        foreach ($this->addonFolders as $addonName => $addonFolder) {
            // back up first
            if (ee('Filesystem')->exists(PATH_THIRD . $addonName)) {
                ee('Filesystem')->copy(PATH_THIRD . $addonName, $this->path() . 'backup/' . $addonName . '_' . $backupFolderId);
                ee('Filesystem')->deleteDir(PATH_THIRD . $addonName);
            }
            try {
                ee('Filesystem')->copy($addonFolder, PATH_THIRD . $addonName);
            } catch (\Exception $e) {
                if (ee('Filesystem')->exists($this->path() . 'backup/' . $addonName . '_' . $backupFolderId)) {
                    // Remove the copied files
                    ee('Filesystem')->deleteDir(PATH_THIRD . $addonName);

                    // Restore from backup
                    ee('Filesystem')->copy($this->path() . 'backup/' . $addonName . '_' . $backupFolderId, PATH_THIRD . $addonName);
                }
                $failedToMove[] = 'addons/' . $addonName;
            }
        }

        if (!empty($this->themesFolders)) {
            // copy themes if we have them
            foreach ($this->themesFolders as $folderName => $themesFolder) {
                // back up first
                if (ee('Filesystem')->exists(PATH_THIRD_THEMES . $folderName)) {
                    ee('Filesystem')->copy(PATH_THIRD_THEMES . $folderName, $this->path() . 'backup_themes/' . $folderName . '_' . $backupFolderId);
                    ee('Filesystem')->deleteDir(PATH_THIRD_THEMES . $folderName);
                }
                try {
                    ee('Filesystem')->copy($themesFolder, PATH_THIRD_THEMES . $folderName);
                } catch (\Exception $e) {
                    if (ee('Filesystem')->exists($this->path() . 'backup_themes/' . $folderName . '_' . $backupFolderId)) {
                        // Remove the copied files
                        ee('Filesystem')->deleteDir(PATH_THIRD_THEMES . $folderName);

                        // Restore from backup
                        ee('Filesystem')->copy($this->path() . 'backup_themes/' . $folderName . '_' . $backupFolderId, PATH_THIRD_THEMES . $folderName);
                    }
                    $failedToMove[] = 'themes/' . $folderName;
                }
            }
        }

        // remove the leftover files
        ee('Filesystem')->deleteDir($this->getExtractedArchivePath());
        if (empty($failedToMove)) {
            if (ee('Filesystem')->exists($this->path() . 'backup')) {
                ee('Filesystem')->deleteDir($this->path() . 'backup');
            }
            if (ee('Filesystem')->exists($this->path() . 'backup_themes')) {
                ee('Filesystem')->deleteDir($this->path() . 'backup_themes');
            }

            if ($deleteZip) {
                ee('Filesystem')->delete($this->getArchiveFilePath());
            }

            $addon = ee('pro:Addon')->get($addonShortName);
            $addonName = !is_null($addon) ? $addon->getName() : $addonShortName;
            $resultMessage = sprintf(lang('addons_unpack_complete'), $addonName);
        } else {
            $resultMessage = sprintf(lang('addons_unpack_failed'), implode(', ', $failedToMove));
        }

        return $resultMessage;
    }

    /**
     * Look for add-on and themes folders
     *
     * @param string $directory
     * @param string $addonShortName
     * @return void
     */
    private function iterateForAddon($directory, $addonShortName)
    {
        $allAddonShortNames = array_keys($this->addonFolders);
        $directory = realpath($directory);

        $files = new FilesystemIterator($directory, FilesystemIterator::UNIX_PATHS | FilesystemIterator::SKIP_DOTS);
        foreach ($files as $item) {
            // is this the addon folder?
            if ($item->getBasename() == 'addon.setup.php') {
                $this->addonFolders[$addonShortName] = $directory;
                break;
            }
            // if it's not add-on folder, is this a themes folder?
            $normalizedPath = str_replace('\\', '/', $item->getPathname());
            // folder matching add-on name in themes
            foreach ($allAddonShortNames as $slug) {
                if (strpos($normalizedPath, '/themes/' . $slug) !== false && strpos($normalizedPath, '/themes/' . $slug) === strlen($normalizedPath) - strlen('/themes/' . $slug) && $item->isDir()) {
                    $this->themesFolders[$item->getBasename()] = $item->getPathname();
                    break;
                }
            }
            // all folders in themes / user
            if (strpos($normalizedPath, '/themes/user') !== false && strpos($normalizedPath, '/themes/user') === strlen($normalizedPath) - strlen('/themes/user') && $item->isDir()) {
                $themeFiles = new FilesystemIterator($normalizedPath, FilesystemIterator::UNIX_PATHS | FilesystemIterator::SKIP_DOTS);
                foreach ($themeFiles as $item) {
                    if ($item->isDir()) {
                        $this->themesFolders[$item->getBasename()] = $item->getPathname();
                    }
                }
                break;
            }
            // go deeper
            if ($item->isDir() && !in_array(realpath($item->getPathname()), $this->addonZipFolders)) {
                return $this->iterateForAddon($item->getPathname(), $item->getBasename());
            }
        }

        $this->addonZipFolders[] = $directory;

        // go up one level
        while ($directory != realpath($this->getExtractedArchivePath())) {
            $directory = realpath($directory . '/..');
            if (!in_array($directory, $this->addonZipFolders)) {
                return $this->iterateForAddon($directory, $addonShortName);
            }
        }
    }
}

// EOF
