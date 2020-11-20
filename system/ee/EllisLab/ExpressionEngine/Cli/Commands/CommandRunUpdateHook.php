<?php

namespace EllisLab\ExpressionEngine\Cli\Commands;

use EllisLab\ExpressionEngine\Cli\Cli;

/**
 * Update or upgrade EE
 */
class CommandRunUpdateHook extends Cli
{

    /**
     * name of command
     * @var string
     */
    public $name = 'Run Update Hook';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'run-update-hook';

    /**
     * Public description of command
     * @var string
     */
    public $description = 'Runs specific update hooks from your upgrade.config file (advanced)';

    /**
     * Summary of command functionality
     * @var [type]
     */
    public $summary = 'This will run one of the preflight or postflight hooks as defined in the upgrade.config file. This can be a destructive action, so use with caution.';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli run-update-hook functionName';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [];

    /**
     * sets the possible upgrade hooks
     * @var array
     */
    private $hooks;

    /**
     * The upgraded file confi
     * @var [type]
     */
    private $upgradeConfigFile;


    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $this->getConfigFile();

        $this->setHooks();

        foreach ($this->arguments as $hook) {
            if (array_key_exists($hook, $this->hooks)) {
                $this->info("Running {$hook}");

                call_user_func($this->hooks[$hook]);
            } else {
                $this->error("Hook {$hook} not found.");
            }
        }

        $this->complete('Success!');
    }

    private function getConfigFile()
    {
        if ($this->option('no-config-file')) {
            return;
        }

        $path = $this->ask('What is the path to your upgrade.config.php? (defaults to SYSPATH)');

        if (! ($customConfig = $this->getConfigPath($path))) {
            $this->fail('Custom config not found.');
        }

        $this->upgradeConfigFile = include $customConfig;
    }

    private function setHooks()
    {
        $this->hooks = array_merge(
            $this->upgradeConfigFile['preflight_hooks'],
            $this->upgradeConfigFile['postflight_hooks']
        );
    }

    private function getConfigPath($path)
    {
        $customConfig = ($path ? rtrim($path, '/') : SYSPATH) . '/upgrade.config.php';

        if (! file_exists($customConfig)) {
            return false;
        }

        return $customConfig;
    }
}
