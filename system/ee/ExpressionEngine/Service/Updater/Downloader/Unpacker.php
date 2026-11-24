<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Updater\Downloader;

use ExpressionEngine\Service\Updater\Downloader\UpdaterPaths;
use ExpressionEngine\Service\Updater\UpdaterException;
use ExpressionEngine\Service\Updater\Verifier;
use ExpressionEngine\Service\Updater\PackageSignature;
use ExpressionEngine\Service\Updater\Logger;
use ExpressionEngine\Service\Updater\RequirementsCheckerLoader;
use ExpressionEngine\Library\Filesystem\Filesystem;
use ZipArchive;

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
    protected $signature;

    protected $manifest_location = 'system/ee/installer/updater/hash-manifest';

    /**
     * Constructor
     *
     * @param	Filesystem $filesystem Filesystem service object
     * @param	ZipArchive $zip_archive PHP-native ZipArchive object
     * @param	Verifier $verifier File verifier object
     * @param	Logger $logger 	Updater logger object
     * @param	RequirementsCheckerLoader $requirements Requirements checker loader object
     * @param	PackageSignature $signature Package signature checker, defaults to one using the bundled keys
     */
    public function __construct(Filesystem $filesystem, ZipArchive $zip_archive, Verifier $verifier, Logger $logger, RequirementsCheckerLoader $requirements, ?PackageSignature $signature = null)
    {
        $this->signature = $signature ?: new PackageSignature();
        $this->filesystem = $filesystem;
        $this->zip_archive = $zip_archive;
        $this->verifier = $verifier;
        $this->logger = $logger;
        $this->requirements = $requirements;
    }

    /**
     * Unzips package to extract folder, once it is known to be the package
     * that was verified when it arrived
     *
     * @param	array	$package	Path of the package's zip file, with the
     *   signature and hash it was verified with
     * @param	string|null	$announced	Version this update was checked as
     *   before the package arrived, when that is known
     */
    public function unzipPackage(array $package, $announced = null)
    {
        $this->verifyPackage($package);

        if (($response = $this->zip_archive->open($package['path'])) !== true) {
            throw new UpdaterException(
                sprintf(
                    lang('could_not_unzip') . "\n\n" . lang('try_again_later'),
                    $response
                ),
                8
            );
        }

        try {
            $version = $this->versionInOpenPackage();

            // Whether this is a major upgrade was settled from the version
            // that was announced. A package for another major version is a
            // different update, and an older one would take the site
            // backwards, however good its signature. A later release of the
            // same major version is let through, and a preview build holds
            // the version it previews.
            if ($announced !== null && ((int) $version !== (int) $announced || version_compare($version, $announced, '<'))) {
                throw new UpdaterException(
                    sprintf(lang('package_not_announced_release'), $version, $announced) . "\n\n" . lang('try_again_later'),
                    26
                );
            }

            // With or without an announced version to go by, the site is
            // never taken back to a release older than the one it runs
            if (version_compare($version, $this->installedVersion(), '<')) {
                throw new UpdaterException(
                    sprintf(lang('package_older_than_installed'), $version, $this->installedVersion()) . "\n\n" . lang('try_again_later'),
                    27
                );
            }

            $this->logger->log('Unzipping package');

            $this->filesystem->mkDir($this->getExtractedArchivePath());
            $this->zip_archive->extractTo($this->getExtractedArchivePath());
        } finally {
            $this->zip_archive->close();
        }

        $this->logger->log('Package unzipped');
    }

    /**
     * The ExpressionEngine version whose files are in place now
     *
     * @return	string	Version number, e.g. 7.5.29
     */
    protected function installedVersion()
    {
        return APP_VER;
    }

    /**
     * Nothing is extracted from a package unless it is still the file that
     * was verified and its signature checks out here too. This step can be
     * reached without the one that fetched the package having run first.
     *
     * @param	array	$package	Package path, signature and hash
     */
    private function verifyPackage(array $package)
    {
        $this->logger->log('Verifying package before unzipping');

        $package += ['path' => '', 'signature' => '', 'hash' => ''];

        // The filesystem service remembers what it has read, so the file is
        // hashed as it is on disk now, which is what is about to be opened
        $hash = (is_string($package['path']) && is_file($package['path']))
            ? hash_file('sha384', $package['path'])
            : false;

        if (! is_string($hash)
            || ! is_string($package['hash'])
            || ! hash_equals($package['hash'], $hash)
            || ! $this->signature->verify($hash, $package['signature'])) {
            throw new UpdaterException(lang('could_not_verify_before_unzip') . "\n\n" . lang('try_again_later'), 7);
        }
    }

    /**
     * Reads the ExpressionEngine version a package holds, without extracting
     * it or running anything inside it
     *
     * @param	string	$path	Path to the package's zip file
     * @return	string	Version number, e.g. 7.5.29
     */
    public function packageVersion($path)
    {
        if ($this->zip_archive->open($path) !== true) {
            throw new UpdaterException(lang('package_version_not_found'), 24);
        }

        try {
            return $this->versionInOpenPackage();
        } finally {
            $this->zip_archive->close();
        }
    }

    /**
     * The ExpressionEngine version held by the package that is open now
     *
     * @return	string	Version number, e.g. 7.5.29
     */
    private function versionInOpenPackage()
    {
        $source = $this->zip_archive->getFromName('system/ee/legacy/libraries/Core.php');

        // One version, however it is quoted and however often it is stated,
        // or the package cannot be trusted to say what it is
        if (! is_string($source)
            || ! preg_match_all('/define\(\s*([\'"])APP_VER\1\s*,\s*([\'"])([^\'"]+)\2\s*\)/', $source, $matches)
            || count(array_unique($matches[3])) !== 1) {
            throw new UpdaterException(lang('package_version_not_found'), 24);
        }

        return $matches[3][0];
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
}

// EOF
