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
use ExpressionEngine\Cli\Commands\Upgrade\UpgradeMap;
use ExpressionEngine\Cli\Commands\Upgrade\UpgradeUtility;

/**
 * Update or upgrade EE
 */
class CommandUpdate extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Update';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'update';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php update';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'rollback'             => 'command_update_option_rollback',
        'verbose,v'            => 'command_update_option_verbose',
        'microapp'             => 'command_update_option_microapp',
        'step:'                => 'command_update_option_step:',
        'no-bootstrap'         => 'command_update_option_no_bootstrap',
        'force-addon-upgrades' => 'command_update_option_force_addon_upgrades',
        'y'                    => 'command_update_option_y',
        'skip-cleanup'         => 'command_update_option_skip_cleanup',
    ];

    protected $verbose;
    protected $isRollback;
    protected $isMicroapp;
    protected $shouldBootstrap;
    protected $step;
    protected $defaultToYes;
    protected $avatarPath;

    public $currentVersion;
    public $updateType;
    public $updateVersion;

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        // Preflight checks, download and unpack update
        $this->initUpgrade();

        if (version_compare($this->currentVersion, $this->updateVersion, '>=')) {
            $this->complete('ExpressionEngine ' . $this->currentVersion . lang('command_update_is_already_up_to_date'));
        }

        // Advanced param checks
        $this->checkAdvancedParams();
        $this->info(lang('command_update_new_version_available') . " {$this->updateVersion}\n");

        if (! $this->defaultToYes) {
            $this->confirm('command_update_confirm_upgrade', false, ['required' => true, 'error_message' => 'command_update_not_run']);
        }

        $this->runUpgrade();
        $this->postFlightCheck();
        $this->complete('command_update_success');
    }

    protected function runUpdater($step = null, $microapp = false, $noBootstrap = false, $rollback = false)
    {
        try {
            // Lets autoload the updates folder since we're ready for it
            if ($step === 'checkForDbUpdates') {
                $this->autoload(SYSPATH . 'ee/installer/updates/');
            }

            if ($microapp) {
                return $this->updaterMicroapp($step);
            }

            if ($rollback) {
                return $this->updaterMicroapp('rollback');
            }

            $this->runUpdater($step, true, true); //'update --microapp --no-bootstrap');
        } catch (\Exception $e) {
            $this->fail("{$e->getCode()}: {$e->getMessage()}");
        }
    }

    /**
     * creates micro app
     * @param  $step
     * @return null
     */
    private function updaterMicroapp($step = null)
    {
        if (! class_exists('ExpressionEngine\Updater\Service\Updater\Runner')) {
            $this->loadMicroapp();
        }

        $runner = new \ExpressionEngine\Updater\Service\Updater\Runner();

        if (! $step) {
            $step = $runner->getFirstStep();
        }

        $runner->runStep($step);

        // runUpdater($step = null, $microapp = false, $noBootstrap = false, $rollback = false)

        // Perform each step as its own command so we can control the scope of
        // files loaded into the app's memory
        if (($next_step = $runner->getNextStep()) !== false) {
            if ($next_step == 'rollback') {
                return $this->runUpdater(null, false, false, true); // 'upgrade --rollback'
            }
            $this->runUpdater($next_step, true, $next_step == 'updateFiles');
        }
    }

    /**
     * begins upgrade
     * @return null
     */
    private function initUpgrade()
    {
        $this->getCurrentVersion();

        defined('CLI_VERBOSE') || define('CLI_VERBOSE', $this->option('-v', false));
        defined('PATH_CACHE') || define('PATH_CACHE', SYSPATH . 'user/cache/');
        defined('PATH_THIRD') || define('PATH_THIRD', SYSPATH . 'user/addons/');
        defined('PATH_THEMES') || define('PATH_THEMES', SYSPATH . '../themes/');
        defined('APP_VER') || define('APP_VER', $this->currentVersion);
        defined('IS_CORE') || define('IS_CORE', false);
        defined('DOC_URL') || define('DOC_URL', 'https://docs.expressionengine.com/latest/');
        defined('EE_APPPATH') || define('EE_APPPATH', BASEPATH);

        $this->verbose = CLI_VERBOSE;
        $this->defaultToYes = $this->option('-y', false);

        // Load what we need in EE
        ee()->load->library('core');

        ee()->load->helper('language');
        ee()->load->helper('string');
        // We only need this one for the upgrade.
        ee()->load->helper('cli');
        ee()->load->driver('cache');

        // Load database
        // If this is running form an earlier version of EE < 3.0.0
        // We'll load the DB the old fashioned way
        $db_config_path = SYSPATH . '/user/config/database.php';
        if (is_file($db_config_path)) {
            require $db_config_path;
            ee()->config->_update_dbconfig($db[$active_group], true);
        }

        // We alsoneed to check the avatar path
        $this->setAvatarPath();

        // Load the database
        $databaseConfig = ee()->config->item('database');
        ee()->load->database();
        ee()->db->swap_pre = 'exp_';
        ee()->db->dbprefix = isset($databaseConfig['expressionengine']['dbprefix'])
                                ? $databaseConfig['expressionengine']['dbprefix']
                                : 'exp_';
        ee()->db->db_debug = false;

        ee()->load->add_package_path(BASEPATH);
        ee()->load->library('functions');
        ee()->load->library('extensions');
        ee()->load->library('api');
        ee()->load->library('localize');
        ee()->load->helper('language');
        // ee()->lang->loadfile('installer');
        ee()->load->library('progress');
        // ee()->load->model('installer_template_model', 'template_model');

        if (!isset(ee()->addons)) {
            ee()->load->library('addons');
            ee('App')->setupAddons(SYSPATH . 'ee/ExpressionEngine/Addons/');
            ee('App')->setupAddons(PATH_THIRD);
        }

        $this->getUpgradeInfo();
    }

    private function getCurrentVersion()
    {
        $version = ee()->config->item('app_version');
        $this->currentVersion = (strpos($version, '.') == false)
            ? $version = implode('.', str_split($version, 1))
            : $version;
    }

    /**
     * load necessary files for microapp
     * @return void
     */
    private function loadMicroapp()
    {
        $this->autoload(SYSPATH . 'ee/updater/ExpressionEngine/Updater/Library/');
        $this->autoload(SYSPATH . 'ee/updater/ExpressionEngine/Updater/Service/Logger/');

        require_once SYSPATH . 'ee/updater/ExpressionEngine/Updater/Service/Updater/SteppableTrait.php';
        require_once SYSPATH . 'ee/updater/ExpressionEngine/Updater/Service/Updater/LegacyFiles.php';
        require_once SYSPATH . 'ee/updater/ExpressionEngine/Updater/Service/Updater/Logger.php';
        require_once SYSPATH . 'ee/updater/ExpressionEngine/Updater/Service/Updater/Verifier.php';
        require_once SYSPATH . 'ee/updater/ExpressionEngine/Updater/Service/Updater/FileUpdater.php';
        require_once SYSPATH . 'ee/updater/ExpressionEngine/Updater/Service/Updater/DatabaseUpdater.php';
        require_once SYSPATH . 'ee/updater/ExpressionEngine/Updater/Service/Updater/Runner.php';
    }

    /**
     * autoload directories for microapp
     * @param  string $dir
     * @return void
     */
    private function autoload($dir)
    {
        if (!is_dir($dir)) {
            throw new \Exception("Could not autoload missing directory: " . $dir, 1);
        }

        foreach (scandir($dir) as $file) {
            if (is_dir($dir . $file) && substr($file, 0, 1) == '.') {
                continue;
            }
            if (is_dir($dir . $file)) {
                $this->autoload($dir . $file . '/');
            }

            // php file?
            if (
                substr($file, 0, 2) !== '._'
                && preg_match("/.php$/i", $file)) {
                include $dir . $file;
            }
        }
    }

    /**
     * check for any possible destructive params during upgrader
     * @return void
     */
    private function checkAdvancedParams()
    {
        if ($this->option('--force-addon-upgrades', false)) {
            $this->write("<<red>>" . lang('command_update_indicated_upgrade_all_addons') . "<<reset>>");
            if ($this->defaultToYes || $this->confirm('command_update_confirm_addon_upgrade')) {
                defined('CLI_UPDATE_FORCE_ADDON_UPDATE') || define('CLI_UPDATE_FORCE_ADDON_UPDATE', $this->option('--force-addon-upgrades', false));
            } else {
                $this->write('<<red>>' . lang('command_update_addon_update_halted') . '<<reset>>');
            }
        }
    }

    private function getUpgradeInfo()
    {
        return $this->doesInstallerFolderExist()
                ? $this->getUpgradeVersionFromLocal()
                : $this->getUpgradeVersionFromCurl();
    }

    private function doesInstallerFolderExist()
    {
        $path = SYSPATH . '/ee/installer';

        return file_exists($path) && is_dir($path);
    }

    private function getUpgradeVersionFromLocal()
    {
        $this->autoload(SYSPATH . 'ee/installer/controllers/');

        if (! class_exists('Wizard')) {
            return $this->getUpgradeVersionFromCurl();
        }

        $wizard = get_class_vars('Wizard');

        $this->info('command_update_getting_info_from_local_env');

        $this->updateType = 'local';
        $this->updateVersion = $wizard['version'];
    }

    private function getUpgradeVersionFromCurl()
    {
        $this->info('command_update_getting_info_from_ee_com');
        ee()->load->library('el_pings');
        $version_file = ee()->el_pings->get_version_info(true);
        $this->updateType == 'curl';
        $this->updateVersion = $version_file['latest_version'];
    }

    protected function runUpgrade()
    {
        try {
            $result = $this->updateType == 'local'
                    ? $this->upgradeFromLocalVersion()
                    : $this->upgradeFromDownloadedVersion();
        } catch (\Exception $e) {
            $this->fail([
                lang('command_update_updater_failed'),
                $e->getMessage(),
                $e->getTraceAsString(),
            ]);
        }
    }

    protected function upgradeFromLocalVersion()
    {
        if (file_exists(FCPATH . '../../.env.php') && (require FCPATH . '../../.env.php') == true) {
            if (getenv('EE_INSTALL_MODE') !== 'TRUE') {
                throw new \Exception("EE_INSTALL_MODE needs to be set to TRUE in .env.php to run update command");
            }
        }

        // We have to initialize differently for local files
        require_once SYSPATH . 'ee/installer/updater/ExpressionEngine/Updater/Service/Updater/SteppableTrait.php';
        require_once SYSPATH . 'ee/installer/core/Installer_Config.php';

        $this->autoload(SYSPATH . 'ee/installer/updates/');

        ee()->load->add_package_path(SYSPATH . 'ee/installer/');
        //ee()->load->library('session');
        ee()->load->library('smartforge');
        ee()->load->library('logger');
        ee()->load->library('update_notices');
        defined('USERNAME_MAX_LENGTH') || define('USERNAME_MAX_LENGTH', 75);
        defined('PASSWORD_MAX_LENGTH') || define('PASSWORD_MAX_LENGTH', 72);
        defined('URL_TITLE_MAX_LENGTH') || define('URL_TITLE_MAX_LENGTH', 200);
        defined('PATH_CACHE') || define('PATH_CACHE', SYSPATH . 'user/cache/');
        defined('PATH_TMPL') || define('PATH_TMPL', SYSPATH . 'user/templates/');
        defined('DOC_URL') || define('DOC_URL', 'https://docs.expressionengine.com/latest/');

        // Load versions of EE
        $upgradeMap = UpgradeMap::getVersionsSupported();

        $next_version = $this->currentVersion;

        // For early version 2, we didn't use dots.
        if (strpos($next_version, '.') == false) {
            $next_version = implode('.', str_split($next_version, 1));
        }

        $currentVersionKey = array_search($next_version, $upgradeMap);
        $end_version = $this->updateVersion;

        // This will loop through all versions of EE
        do {
            $currentVersionKey--;

            if (!isset($upgradeMap[$currentVersionKey])) {
                $this->fail(lang('command_update_error_updater_failed_missing_version') . $currentVersionKey);
            }

            $next_version = $upgradeMap[$currentVersionKey];

            $this->info(lang('command_update_updating_to_version') . $next_version);

            // Instantiate the updater class
            if (class_exists('Updater')) {
                $UD = new Updater();
            } else {
                $class = '\ExpressionEngine\Updater\Version_' . str_replace('.', '_', $next_version) . '\Updater';
                $UD = new $class();
            }

            if (($status = $UD->do_update()) === false) {
                $errors = isset($UD->errors)
                            ? $UD->errors
                            : [];

                $errorText = array_merge(
                    [
                        lang('command_update_failed_on_version') . $next_version
                    ],
                    $errors
                );

                $this->fail($errorText);
            }

            ee()->config->set_item('app_version', $upgradeMap[$currentVersionKey]);
            ee()->config
                ->_update_config([
                    'app_version' => $upgradeMap[$currentVersionKey]
                ]);
        } while (version_compare($next_version, $end_version, '<'));

        if (!$this->option('--skip-cleanup', false)) {
            // Complete upgrades
            UpgradeUtility::run();
        }
    }

    protected function upgradeFromDownloadedVersion()
    {
        try {
            ee('Updater/Runner')->run();
        } catch (\Exception $e) {
            $this->fail("{$e->getCode()}: {$e->getMessage()}\n\n\n{$e->getTraceAsString()}");
        }

        $this->runUpdater();
    }

    protected function setAvatarPath()
    {
        if (version_compare($this->currentVersion, '3.0.0', '<')) {
            if (! ee()->config->item('avatar_path')) {
                $this->info('command_update_missing_avatar_path_message');
                $guess = ee()->config->item('base_path') ? rtrim(ee()->config->item('base_path'), '/') . '/images/avatars' : SYSPATH . '../images/avatars';
                $result = ($this->defaultToYes || $this->confirm('Use ' . $guess . '?'))
                        ? $guess
                        : $this->ask('command_update_enter_full_avatar_path');

                ee()->config->_update_config([
                    'avatar_path' => $result,
                ]);
            }
        }
    }

    protected function postFlightCheck()
    {
        $version = $this->getCurrentVersion();
        $versionNamingMap = UpgradeMap::$versionNaming;
        if (empty($version)) {
            $version = $this->currentVersion;
        }

        if (isset($versionNamingMap[$version])) {
            ee()->config
                ->_update_config([
                    'app_version' => $versionNamingMap[$version]
                ]);
        }

        // reset the flag for dismissed banner for members
        ee('db')->update('members', ['dismissed_banner' => 'n']);
    }
}
