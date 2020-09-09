<?php

namespace EllisLab\ExpressionEngine\Cli\Commands;

use EllisLab\ExpressionEngine\Cli\Cli;
use EllisLab\ExpressionEngine\Cli\Commands\Upgrade\UpgradeMap;
use EllisLab\ExpressionEngine\Library\Filesystem\Filesystem;

class CommandPrepareUpgrade extends Cli
{

    /**
     * name of command
     * @var string
     */
    public $name = 'Prepare Upgrade';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'prepare-upgrade';

    /**
     * Public description of command
     * @var string
     */
    public $description = 'Prepare a different site to be upgraded using these files';

    /**
     * Summary of command functionality
     * @var [type]
     */
    public $summary = 'This command copies all files necessary for upgrading into a different ExpressionEngine site and restructures it';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli prepare-upgrade';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'upgrade-ee'                => 'Start the upgrade after moving files',
        'force-add-on-upgrade'      => 'After upgrading EE, runs addon upgrades',
        'old-base-path:'            => 'Absolute path of old site',
        'new-base-path:'            => 'Absolute path of new site',
        'old-public-path:'          => 'Absolute path of old site public path',
        'new-public-path:'          => 'Absolute path of new site public path',
        'no-config-file'            => 'Ignores the config file and doesn\'t check for it',
        'ee-version'                => 'The current site ',
        'should-move-system-path'   => 'Whether the upgrade process should move the old theme folder to the new site',
        'old-system-path:'          => 'Absolute path of old site system folder',
        'new-system-path:'          => 'Absolute path of new site system folder',
        'should-move-template-path' => 'Whether the upgrade process should move the old template folder to the new site',
        'old-template-path:'        => 'Absolute path of old site template folder',
        'new-template-path:'        => 'Absolute path of new site template folder',
        'should-move-theme-path'    => 'Whether the upgrade process should move the old theme folder to the new site',
        'old-theme-path:'           => 'Absolute path of old site user theme folder',
        'new-theme-path:'           => 'Absolute path of new site user theme folder',
        'run-preflight-hooks'       => 'Whether the upgrade process should run defined preflight hooks',
        'run-postflight-hooks'      => 'Whether the upgrade process should run defined postflight hooks',
        'temp-directory'            => 'The directory we work magic in',
    ];

    protected $upgradeConfigFile = [];

    protected $upgradeConfig = [];

    protected $configKeys = [
        'upgrade_ee'                => 'bool',
        'ee_version'                => 'string',
        'old_base_path'             => 'string',
        'new_base_path'             => 'string',
        'should_move_system_path'   => 'bool',
        'old_system_path'           => 'string',
        'new_system_path'           => 'string',
        'should_move_template_path' => 'bool',
        'old_template_path'         => 'string',
        'new_template_path'         => 'string',
        'should_move_theme_path'    => 'bool',
        'old_theme_path'            => 'string',
        'new_theme_path'            => 'string',
        'run_preflight_hooks'       => 'bool',
        'run_postflight_hooks'      => 'bool',
        'preflight_hooks'           => 'array',
        'postflight_hooks'          => 'array',
        'old_public_path'           => 'string',
        'new_public_path'           => 'string',
        'temp_directory'            => 'string',
    ];

    protected $filemap;

    /**
     * Command can run without EE Core
     * @var boolean
     */
    public $standalone = true;

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $this->info('Preparing the upgrade for a site.');

        // Collect all the info we need before we can start
        // pull in the config
        $this->preloadConfig();

        $this->preChecks();

        $this->runPreflightHooks();

        $this->moveOriginalSiteToTmp();
        $this->copyNewEEFiles();
        $this->copyOriginalConfig();

        // Move templates
        $this->moveTemplates();
        
        // @TODO: Move addons
        $this->moveAddons();

        // Done preparing - if they wanted the upgrade to be run after, run it here
        if ($upgrade = ($this->option('upgrade-ee') || $this->upgradeConfig['upgrade_ee'])) {
            $this->info('Running EE upgrade');
            $this->runUpgrade();
        }

        $this->runPostflightHooks();

        $this->complete('Process complete!');
    }

    private function runPreflightHooks()
    {
        if (!$this->upgradeConfig['run_preflight_hooks']) {
            return;
        }

        $this->info('Running preflight hooks');

        foreach ($this->upgradeConfig['preflight_hooks'] as $hook) {
 
            call_user_func_array($hook, func_get_args());

        }
    }

    private function runPostflightHooks()
    {
        if (!$this->upgradeConfig['run_postflight_hooks']) {
            return;
        }

        $this->info('Running postflight hooks');

        foreach ($this->upgradeConfig['postflight_hooks'] as $hook) {

            call_user_func_array($hook, func_get_args());

        }
    }

    private function preChecks()
    {
        $this->info("Here's how things are configured:");

        foreach ($this->upgradeConfig as $upgradeKey => $upgradeValue) {
            if (is_bool($upgradeValue)) {
                $upgradeValue = $upgradeValue ? 'true' : 'false';
            }

            if(! is_array($upgradeValue)) {
                $this->write("<<green>>{$upgradeKey}<<reset>>: {$upgradeValue}");
            }
        }

        $this->info("We are about to move X file to tmp/X and Y to system/Y");
        $this->info("Make sure you have backups!");

        $continue = $this->confirm('Are you sure you want to proceed?');

        // User does not want to continue - abort!
        if (! $continue) {
            $this->error('Upgrade aborted');
            exit;
        }

        // Check if upgrade too
        if ($this->option('upgrade-ee')) {
            $this->info("You also said you want to upgrade EE after moving these files around.");

            $continue = $this->confirm('Are you sure you want to proceed?');

            // User does not want to continue - abort!
            if (! $continue) {
                $this->error('Upgrade aborted');
                exit;
            }
        }

        return true;
    }

    private function preloadConfig()
    {
        // First up, load config file
        $this->getConfigFile();
        $this->parseConfig();
    }

    private function getConfigFile()
    {
        if ($this->option('no-config-file')) {
            return;
        }

        $filesystem = new Filesystem;

        $customConfig = $this->getConfigPath();

        if( ! $customConfig) {
            $path = $this->ask('What is the path to your upgrade.config.php? (defaults to SYSPATH, currently ' . SYSPATH . ')');

            if (! ($customConfig = $this->getConfigPath($path))) {
                $this->error('Custom config not found.');
                return;
            }
        }

        $this->upgradeConfigFile = include $customConfig;
    }

    private function runUpgrade()
    {
        $command = "php {$this->upgradeConfig['new_base_path']}/eecli update -y";

        if ($this->option('--force-add-on-upgrade')) {
            $command .= ' --force-addon-upgrades';
        }

        exec($command);
    }

    private function parseConfig()
    {
        foreach ($this->configKeys as $key => $type) {
            $value = null;

            $flag = str_replace('_', '-', $key);

            // Check for the flag
            if ($flagData = $this->option($flag, false)) {
                $value = $flagData;
            }

            // If no flag, check the file
            if (!$value && isset($this->upgradeConfigFile[$key])) {
                $value = $this->upgradeConfigFile[$key];
            }

            // If neither of those, we'll ask
            if (!$value) {
                if ($type == 'array') {
                    $value = [];
                }

                if ($type == 'string') {
                    $value = $this->ask("Please set {$key}:");
                }

                if ($type == 'bool') {
                    $value = $this->confirm("{$key}?");
                }
            }

            $this->upgradeConfig[$key] = $value;
        }

        // Load upgrade file map
        if ($this->upgradeConfigFile['upgrade_map']) {
            $filemap = $this->upgradeConfigFile['upgrade_map'];
        } else {
            $version = $this->upgradeConfig['ee_version'];

            $filemap = UpgradeMap::get($version);
        }

        $this->filemap = UpgradeMap::prepare($filemap);
    }

    private function getConfigPath($path = null)
    {
        $customConfig = ($path ? rtrim($path, '/') : SYSPATH) . '/upgrade.config.php';

        if (! file_exists($customConfig)) {
            return false;
        }

        return $customConfig;
    }

    private function moveOriginalSiteToTmp()
    {
        // Need to copy system/, index.php, admin.php and themes folder
        $filesystem = new Filesystem;

        $tmp_folder = $this->prepTmpDirectory();

        $filesystem->rename($this->upgradeConfig['old_public_path'] . $this->filemap['index_file_old'], $tmp_folder . $this->filemap['index_file_old']);
        $filesystem->rename($this->upgradeConfig['old_public_path'] . $this->filemap['admin_file_old'], $tmp_folder . $this->filemap['admin_file_old']);

        $filesystem->rename($this->upgradeConfig['old_system_path'], $tmp_folder . 'system');

        if ($this->upgradeConfig['should_move_theme_path']) {
            $filesystem->rename($this->upgradeConfig['old_theme_path'], $tmp_folder . 'themes');
        }
    }

    private function prepTmpDirectory()
    {
        $filesystem = new Filesystem;

        $tmp_folder = rtrim($this->upgradeConfig['old_base_path'], '/') . '/' . $this->upgradeConfig['temp_directory'];

        if ($filesystem->isDir($tmp_folder)) {
            $filesystem->delete($tmp_folder);
        } else {
            $filesystem->mkdir($tmp_folder);
        }

        return rtrim($tmp_folder, '/') . '/';
    }

    private function copyNewEEFiles()
    {
        $filesystem = new Filesystem;

        $filesystem->copy(
            SYSPATH,
            $this->upgradeConfig['new_system_path']
        );

        if ($this->upgradeConfig['should_move_theme_path']) {
            $filesystem->copy(
                FCPATH . 'themes',
                $this->upgradeConfig['new_theme_path']
            );
        }

        $filesystem->copy(
            FCPATH . $this->filemap['admin_file'],
            $this->upgradeConfig['new_public_path'] . $this->filemap['admin_file']
        );

        $filesystem->copy(
            FCPATH . $this->filemap['index_file'],
            $this->upgradeConfig['new_public_path'] . $this->filemap['index_file']
        );

        $filesystem->copy(
            FCPATH . 'eecli',
            $this->upgradeConfig['new_base_path'] . 'eecli'
        );
    }

    private function copyOriginalConfig()
    {
        $filesystem = new Filesystem;

        $tmp_folder = rtrim($this->upgradeConfig['old_base_path'], '/') . '/' . $this->upgradeConfig['temp_directory'] . '/';

        // We should check if this is EE2 or EE3+
        if($filesystem->exists($tmp_folder . '/system/expressionengine')) {
            // It's EE2!
            $filesystem->copy(
                $tmp_folder . rtrim($this->filemap['config_path'], '/') . '/' . $this->filemap['config_file'],
                rtrim($this->upgradeConfig['new_system_path'], '/') . '/user/config/' . ltrim($this->filemap['config_file'], '/')
            );

            if(isset($this->filemap['database_file']) && $this->filemap['database_file'] !== 'config.php') {
                $filesystem->copy(
                    $tmp_folder . rtrim($this->filemap['config_path'], '/') . '/' . $this->filemap['database_file'],
                    rtrim($this->upgradeConfig['new_system_path'], '/') . '/user/config/' . ltrim($this->filemap['database_file'], '/')
                );

                $this->info('We found a database file. Please move this information in to config.php');
            }

        } else {
            // It's EE3+!
            $filesystem->copy(
                $tmp_folder . $this->filemap['config_path'] . $this->filemap['config_file'],
                rtrim($this->upgradeConfig['new_system_path'], '/') . '/user/config/' . ltrim($this->filemap['config_file'], '/')
            );

            $filesystem->copy(
                $tmp_folder . $this->filemap['config_path'] . $this->filemap['database_file'],
                rtrim($this->upgradeConfig['new_system_path'], '/') . '/user/config/' . ltrim($this->filemap['database_file'], '/')
            );
        }
    }

    private function moveTemplates()
    {

        if( ! $this->upgradeConfig['should_move_template_path']) {
            return;
        }

        $filesystem = new Filesystem;

        // $tmp_folder = rtrim($this->upgradeConfig['old_base_path'], '/') . '/' . $this->upgradeConfig['temp_directory'] . '/';

        $filesystem->rename(
            rtrim($this->upgradeConfig['old_template_path'], '/'),
            rtrim($this->upgradeConfig['new_template_path'], '/')
        );

    }

    private function moveAddons()
    {

    }

    private function checkIfPathAllowed($path)
    {
        // Check if path exists
        if (! file_exists($path)) {
            $this->fail("{$path} does not exist.");
        }

        // Check if it is a directory
        if (! is_dir($path)) {
            $this->fail("{$path} is not a directory.");
        }

        // Check if it writable
        if (! is_writable($path)) {
            $this->fail("{$path} is not writable.");
        }

        return true;
    }
}
