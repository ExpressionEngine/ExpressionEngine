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
class CommandUpdateDatabase extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'UpdateDatabase';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'update:db';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php update:db';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'rollback'             => 'command_update_option_rollback',
        'verbose,v'            => 'command_update_option_verbose',
        'force,y'                    => 'command_update_option_y',
        'skip-cleanup'         => 'command_update_option_skip_cleanup',
        'to-version:'          => 'command_update_option_to_version',
        'from-version:'        => 'command_update_option_from_version',
    ];

    protected $verbose;
    protected $defaultToYes;
    protected $avatarPath;
    protected $localUpdater = false;
    protected $versions = [
        'from' => null,
        'to' => null,
    ];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $this->defaultToYes = $this->option('-y', false);
        $this->localUpdater = file_exists(SYSPATH . '/ee/installer') && is_dir(SYSPATH . '/ee/installer');

        $this->setVersions();

        $this->setup();

        $this->info(vsprintf(lang('command_update_database_versions'), [$this->versions['from'], $this->versions['to']]));

        if (! $this->defaultToYes) {
            $this->confirm('command_update_confirm_upgrade', false, ['required' => true, 'error_message' => 'command_update_not_run']);
        }

        try {
            $this->localUpdater ? $this->upgradeFromLocalVersion() : $this->upgradeFromDownloadedVersion();
        } catch (\Exception $e) {
            $this->fail([
                lang('command_update_updater_failed'),
                $e->getMessage(),
                $e->getTraceAsString(),
            ]);
        }

        $this->complete('command_update_success');
    }

    protected function setVersions()
    {
        // Validate version option format if provided
        foreach(['--from-version', '--to-version'] as $option) {
            if(!is_null($this->option($option)) && !preg_match('/^(\d+\.)?(\d+\.)?(\*|\d+)$/', $this->option($option))) {
                return $this->fail("$option: ". lang('command_update_invalid_version_number'));
            }
        }

        // Get App Version
        $version = ee()->config->item('app_version');
        $appVersion = (strpos($version, '.') == false) ? implode('.', str_split($version, 1)) : $version;

        // Get Database Version
        $version = ee('Model')->get('Config')->filter('key', '=', 'app_db_version')->first();
        $databaseVersion = $version->value ?? $appVersion;

        // Make sure we are not attempting to upgrade the database past the app version
        if(version_compare($this->option('--to-version', '0.0.0'), $appVersion, '>')) {
            return $this->fail(vsprintf(lang('command_update_database_version_exceeds_app_version'), [
                $this->option('--to-version'),
                $appVersion
            ]));
        }

        $this->versions['to'] = $this->option('--to-version', $appVersion);
        $this->versions['from'] = $this->option('--from-version', $databaseVersion);

        if (version_compare($this->versions['from'], $this->versions['to'], '>=')) {
            return $this->complete(lang('command_update_database_up_to_date') . " [version {$this->versions['from']}]");
        }

        if(!is_null($this->option('--from-version')) &&  $this->option('--from-version') !== $databaseVersion) {
            $this->error(vsprintf(lang('command_update_database_version_mismatch_warning'), [
                $this->versions['from'],
                $databaseVersion
            ]));
        }

        $this->defineConstants([
            'CLI_VERBOSE' => $this->option('-v', false),
            'PATH_CACHE' => SYSPATH . 'user/cache/',
            'PATH_THIRD' => SYSPATH . 'user/addons/',
            'PATH_THEMES' => SYSPATH . '../themes/',
            'APP_VER' => $appVersion,
            'IS_CORE' => false,
            'DOC_URL' => 'https://docs.expressionengine.com/latest/',
            'EE_APPPATH' => BASEPATH,
        ]);
    }

    protected function defineConstants($constants)
    {
        foreach($constants as $key => $value) {
            defined($key) || define($key, $value);
        }
    }

    /**
     * Prepare for upgrade
     * @return null
     */
    private function setup()
    {
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
        if (is_file(SYSPATH . '/user/config/database.php')) {
            require SYSPATH . '/user/config/database.php';
            ee()->config->_update_dbconfig($db[$active_group], true);
        }

        // We also need to check the avatar path
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
        ee()->load->library('progress');

        if (!isset(ee()->addons)) {
            ee()->load->library('addons');
            ee('App')->setupAddons(SYSPATH . 'ee/ExpressionEngine/Addons/');
            ee('App')->setupAddons(PATH_THIRD);
        }
    }

    /**
     * load necessary files for microapp
     * @return void
     */
    private function loadMicroapp()
    {
        if (class_exists('ExpressionEngine\Updater\Service\Updater\Runner')) {
            return;
        }

        $updaterPath = SYSPATH . 'ee/updater/ExpressionEngine/Updater/';

        $this->autoload($updaterPath . 'Library/');
        $this->autoload($updaterPath . 'Service/Logger/');

        require_once $updaterPath . 'Service/Updater/SteppableTrait.php';
        require_once $updaterPath . 'Service/Updater/Logger.php';
        require_once $updaterPath . 'Service/Updater/Verifier.php';
        require_once $updaterPath . 'Service/Updater/FileUpdater.php';
        require_once $updaterPath . 'Service/Updater/DatabaseUpdater.php';
        require_once $updaterPath . 'Service/Updater/Runner.php';
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

    protected function upgradeFromLocalVersion()
    {
        ob_start();
        include(FCPATH . '../../.env.php');
        ob_end_clean();

        if (getenv('EE_INSTALL_MODE') !== 'TRUE') {
            throw new \Exception("EE_INSTALL_MODE needs to be set to TRUE in .env.php to run update command");
        }

        $this->autoload(SYSPATH . 'ee/installer/updates/');

        class_alias('ExpressionEngine\Library\Filesystem\Filesystem', 'ExpressionEngine\Updater\Library\Filesystem\Filesystem');
        class_alias('ExpressionEngine\Service\Updater\UpdaterException', 'ExpressionEngine\Updater\Service\Updater\UpdaterException');
        class_alias('ExpressionEngine\Service\Updater\SteppableTrait', 'ExpressionEngine\Updater\Service\Updater\SteppableTrait');
        class_alias('ExpressionEngine\Service\Updater\Verifier', 'ExpressionEngine\Updater\Service\Updater\Verifier');
        class_alias('ExpressionEngine\Service\Updater\Logger', 'ExpressionEngine\Updater\Service\Updater\Logger');

        require_once SYSPATH . 'ee/installer/core/Installer_Config.php';
        require_once SYSPATH . 'ee/installer/updater/ExpressionEngine/Updater/Service/Updater/DatabaseUpdater.php';
        require_once SYSPATH . 'ee/installer/updater/ExpressionEngine/Updater/Service/Updater/FileUpdater.php';
        require_once SYSPATH . 'ee/installer/updater/ExpressionEngine/Updater/Service/Updater/Runner.php';

        ee()->load->library('smartforge');
        ee()->load->library('logger');

        $this->defineConstants([
            'USERNAME_MAX_LENGTH' =>  75,
            'PASSWORD_MAX_LENGTH' =>  72,
            'URL_TITLE_MAX_LENGTH' =>  200,
            'PATH_CACHE' =>  SYSPATH . 'user/cache/',
            'PATH_TMPL' =>  SYSPATH . 'user/templates/',
            'DOC_URL' =>  'https://docs.expressionengine.com/latest/',
        ]);

        file_put_contents(PATH_CACHE . 'ee_update/configs.json', json_encode([
            'archive_path' => PATH_CACHE . 'ee_update/'
        ]));

        $runner = $this->getUpdaterRunner();

        // When we're working with the local updater we want to avoid the 'selfDestruct' step
        while (($next_step = $runner->getNextStep()) !== false && $next_step !== 'selfDestruct') {
            $runner->runStep($next_step);
        }

        if (!$this->option('--skip-cleanup', false)) {
            // Complete upgrades
            UpgradeUtility::run();
        }
    }

    protected function getUpdaterRunner()
    {
        $runner = (new \ExpressionEngine\Updater\Service\Updater\Runner)
            ->onlyUpdateDatabase()
            ->fromVersion($this->versions['from'])
            ->toVersion($this->versions['to']);

        if($this->option('rollback', false)) {
            if (!file_exists(PATH_CACHE . 'ee_update/database.sql')) {
                return $this->fail('Cannot restore database.  Backup not found at '. PATH_CACHE . 'ee_update/database.sql');
            }

            $runner->setSteps(['restoreDatabase']);
        }

        return $runner;
    }

    protected function upgradeFromDownloadedVersion()
    {
        // The Updater/Runner service handles downloading the update which has
        // its own Runner that will handle updating the database
        try {
            ee('Updater/Runner')->run();
        } catch (\Exception $e) {
            $this->fail("{$e->getCode()}: {$e->getMessage()}\n\n\n{$e->getTraceAsString()}");
        }

        $this->loadMicroapp();

        $runner = $this->getUpdaterRunner();
        $runner->run();
    }

    protected function setAvatarPath()
    {
        if (version_compare($this->versions['to'], '3.0.0', '<')) {
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
}
