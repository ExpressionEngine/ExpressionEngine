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

use ExpressionEngine\Library\Filesystem\Filesystem;
use ExpressionEngine\Service\Updater\Logger;

/**
 * Prep Major Upgrades
 *
 * Unpacks the downloaded ExpressionEngine zip archive, verifies the integrity
 * of the files, checks the downloaded installation's server requirements, and
 * finally moves the micro app into place to facilitate the rest of the upgrade
 */
class PrepMajorUpgrade
{
    protected $filesystem;
    protected $logger;

    /**
     * Constructor
     *
     * @param   Filesystem $filesystem Filesystem service object
     * @param   Logger $logger Updater logger object
     */
    public function __construct(Filesystem $filesystem, Logger $logger)
    {
        $this->filesystem = $filesystem;
        $this->logger = $logger;
    }

    /**
     * Runs all necessary pre-major upgrade steps
     */
    public function isMajorUpgrade($update_version_major = null)
    {
        $app_ver = defined('APP_VER') ? APP_VER : ee()->config->item('app_version');
        $version_major = (int) explode('.', $app_ver, 2)[0];

        if (empty($update_version_major)) {
            ee()->load->library('el_pings');
            $version_file = ee()->el_pings->get_version_info();
            $update_version_major = (int) explode('.', $version_file['latest_version'], 2)[0];
        }

        // Is the upcoming release a major version?
        return ($update_version_major > $version_major);
    }

    /**
     * Runs all necessary pre-major upgrade steps
     */
    public function prepMajorIfApplicable($update_version_major = null)
    {
        // If the next version is a major version upgrade
        if (!$this->isMajorUpgrade($update_version_major)) {
            return false;
        }

        $steps = ['verifyPhpVersion', 'moveAddonsToUser'];

        foreach ($steps as $step) {
            $this->$step();
        }
    }

    private function moveAddonsToUser()
    {
        $this->logger->log(lang('preflight_moving_addons_to_user_folder'));

        // These add-ons are all being removed from EE7, so if they are in use we should
        $addonsToMove = ['forum', 'simple_commerce', 'ip_to_nation'];

        // Loop through all the addons we want to move
        foreach ($addonsToMove as $addonName) {
            // if the addon is installed, lets move it to the user folder
            $addon = ee('Addon')->get($addonName);
            if ($addon && $addon->isInstalled()) {
                // Move the main addon folder
                $systemAddonPath = PATH_ADDONS . $addonName;
                $userAddonPath = PATH_THIRD . $addonName;
                // Check to make sure the directory exists and it doesnt exist in the user folder
                if ($this->filesystem->isDir($systemAddonPath) && !$this->filesystem->isDir($userAddonPath)) {
                    $this->filesystem->rename($systemAddonPath, $userAddonPath);
                }

                // Move the themes folder
                $systemAddonThemesPath = PATH_THEMES . $addonName;
                $userAddonThemesPath = PATH_THIRD_THEMES . $addonName;
                // Check to make sure the directory exists and it doesnt exist in the user folder
                if ($this->filesystem->isDir($systemAddonThemesPath) && !$this->filesystem->isDir($userAddonThemesPath)) {
                    $this->filesystem->rename($systemAddonThemesPath, $userAddonThemesPath);
                }

                // Move the templates folder
                $systemAddonTemplatesPath = SYSPATH . 'ee/templates/_themes/' . $addonName;
                $userAddonTemplatesPath = SYSPATH . 'user/templates/_themes/' . $addonName;
                // Check to make sure the directory exists and it doesnt exist in the user folder
                if ($this->filesystem->isDir($systemAddonTemplatesPath) && !$this->filesystem->isDir($userAddonTemplatesPath)) {
                    if (!$this->filesystem->isDir(SYSPATH . 'user/templates/_themes')) {
                        $this->filesystem->mkDir(SYSPATH . 'user/templates/_themes');
                    }
                    $this->filesystem->rename($systemAddonTemplatesPath, $userAddonTemplatesPath);
                }
            }
        }
    }

    private function verifyPhpVersion()
    {
        $this->logger->log(lang('preflight_verifying_php_version'));

        if (version_compare(phpversion(), '7.2.5', '<')) {
            $error_message = sprintf(lang('preflight_verifying_php_version_error'), phpversion());

            $this->logger->log($error_message);

            throw new \Exception($error_message);
        }
    }
}

// EOF
