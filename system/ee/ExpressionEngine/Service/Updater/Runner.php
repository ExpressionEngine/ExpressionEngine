<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Updater;

use ExpressionEngine\Service\Updater\Logger;
use ExpressionEngine\Service\Updater\SteppableTrait;

/**
 * Handles the first half of an ExpressionEngine upgrade: downloading, verifying,
 * and moving the updater micro app into place
 */
class Runner
{
    use SteppableTrait {
        runStep as runStepParent;
    }

    protected $logger;
    protected $package;
    protected $target_version;

    public function __construct()
    {
        // The idea here is to separate the download and unpacking
        // process into quick, hopefully low-memory tasks when accessed
        // through the browser
        $this->setSteps([
            'preflight',
            'prepMajorUpgrade',
            'download',
            'unpack'
        ]);

        $this->logger = ee('Updater/Logger');

        // Attempt to set time and memory limits
        @set_time_limit(0);
        @ini_set('memory_limit', '256M');
    }

    /**
     * Updates from a package that is already on this server and has been
     * verified, instead of downloading one
     *
     * @param	array	$package	Package details from Updater/Downloader's stagePackage()
     * @param	string	$version	ExpressionEngine version the package holds
     */
    public function usePackage(array $package, $version)
    {
        $this->package = $package;
        $this->target_version = $version;
    }

    // She packed my bags last night...
    public function preflight()
    {
        // Before the log or anything kept from the last update is cleared
        ee('Updater/Preflight')->checkLastUpdateCompleted();

        $this->logger->truncate();
        $this->logger->log('Maximum execution time: ' . @ini_get('max_execution_time'));
        $this->logger->log('Memory limit: ' . @ini_get('memory_limit'));

        // -------------------------------------------
        // 'updater_start' hook.
        //  - added 7.5.16
        //
        if (ee()->extensions->active_hook('updater_start') === true) {
            ee()->extensions->call('updater_start', $this);
            if (ee()->extensions->end_script === true) {
                return;
            }
        }
        //
        // -------------------------------------------

        // Made here, as it reads the site's theme paths when it is made and
        // the hook above may have changed them
        $preflight = ee('Updater/Preflight');
        $preflight->checkPermissions();
        $preflight->cleanUpOldUpgrades();
        $preflight->checkDiskSpace();
        $preflight->stashConfig();
    }

    public function prepMajorUpgrade()
    {
        // A supplied package says which version it holds. Without one, the
        // latest release is the one being updated to.
        $major = $this->target_version ? (int) explode('.', $this->target_version, 2)[0] : null;

        ee('Updater/PrepMajorUpgrade')->prepMajorIfApplicable($major);
    }

    public function download()
    {
        if ($this->package) {
            $this->logger->log('Using the update package that was supplied');

            return;
        }

        $this->package = ee('Updater/Downloader')->downloadPackage(
            'https://update.expressionengine.com'
        );
    }

    public function unpack()
    {
        $unpacker = ee('Updater/Unpacker');

        // In the control panel each step is its own request, so the package's
        // details come from where the download step left them
        $unpacker->unzipPackage($this->package ?: ee('Updater/Downloader')->downloadedPackage());
        $unpacker->verifyExtractedPackage();
        $unpacker->checkRequirements();
        $unpacker->moveUpdater();

        $this->logger->log('Taking the site offline');

        // We'll save the current system on setting
        $this->turnSystemOff();
    }

    public function rollback()
    {
        ee('Filesystem')->deleteDir(SYSPATH . 'ee/updater');
    }

    /**
     * Catch-all exception handler for updater steps to log errors
     */
    public function runStep($step)
    {
        if (REQ == 'CLI') {
            $this->stdout($this->getLanguageForStep($step) . '...');
        }

        try {
            $this->runStepParent($step);
        } catch (\Exception $e) {
            $this->logger->log($e->getMessage());
            $this->logger->log($e->getTraceAsString());

            // -------------------------------------------
            // 'updater_error' hook.
            //  - added 7.5.16
            //
            if (ee()->extensions->active_hook('updater_error') === true) {
                ee()->extensions->call('updater_error', $this, $e);
            }
            //
            // -------------------------------------------

            // Send it up the chain
            throw $e;
        }

        // We may have shifted files around
        if (function_exists('opcache_reset')) {
            // Check for restrict_api path restriction
            if (($opcache_api_path = ini_get('opcache.restrict_api')) && stripos(SYSPATH, $opcache_api_path) !== 0) {
                return;
            }

            opcache_reset();
        }
    }

    public function getLanguageForStep($step)
    {
        ee()->lang->loadfile('updater');

        return lang($step . '_step');
    }

    private function stdout($message)
    {
        $text_color = '[1;37m';

        $arrow_color = '[0;34m';
        $text_color = '[1;37m';

        if (REQ == 'CLI' && ! empty($message)) {
            $message = "\033" . $arrow_color . "==> \033" . $text_color . strip_tags($message) . "\033[0m\n";

            $stdout = fopen('php://stdout', 'w');
            fwrite($stdout, $message);
            fclose($stdout);
        }
    }

    private function turnSystemOff()
    {
        $config = ee('Config')->getFile();
        $config->set('is_system_on_before_updater', $config->get('is_system_on', 'y'), true);
        $config->set('is_system_on', 'n', true);
    }
}
// EOF
