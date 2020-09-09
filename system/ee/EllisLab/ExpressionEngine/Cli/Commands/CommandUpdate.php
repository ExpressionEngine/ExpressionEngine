<?php

namespace EllisLab\ExpressionEngine\Cli\Commands;

use EllisLab\ExpressionEngine\Cli\Cli;
use EllisLab\ExpressionEngine\Cli\Commands\Upgrade\UpgradeMap;
use EllisLab\ExpressionEngine\Cli\Commands\Upgrade\UpgradeUtility;

/**
 * Update or upgrade EE
 */
class CommandUpdate extends Cli {

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
	 * Public description of command
	 * @var string
	 */
	public $description = 'Updates EE';

	/**
	 * Summary of command functionality
	 * @var [type]
	 */
	public $summary = 'This will run the update process of EE';

	/**
	 * How to use command
	 * @var string
	 */
	public $usage = 'php eecli update';

	/**
	 * options available for use in command
	 * @var array
	 */
	public $commandOptions = [
		'rollback'				=> 'Rollsback last update',
		'verbose,v'				=> 'Verbose output',
		'microapp'				=> 'Run as microapp',
		'step:'					=> 'Step in process (param required)',
		'no-bootstrap'			=> 'Runs with no bootstrap',
		'force-addon-upgrades'	=> 'Automatically runs all addon updaters at end of update (advanced)',
		'y'						=> 'Skip all confirmations. Don\'t do this.'
	];

	protected $verbose;
	protected $isRollback;
	protected $isMicroapp;
	protected $shouldBootstrap;
	protected $step;
	protected $defaultToYes;

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

		if (version_compare($this->currentVersion, $this->updateVersion, '>='))
		{
			$this->complete('ExpressionEngine ' . $this->currentVersion . ' is already up-to-date!');
		}

		// Advanced param checks
		$this->checkAdvancedParams();

		$this->info("There is a new version of ExpressionEngine available: {$this->updateVersion}\n");

		if(! $this->defaultToYes) {
			if(! $this->confirm("Would you like to upgrade?")) {
				$this->complete("Update not run");
			}
		}

		$this->runUpgrade();

		$this->complete('Success! Create something awesome!');

	}

	protected function runUpdater($step = null, $microapp = false, $noBootstrap = false, $rollback = false)
	{

		try
		{
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
		if ( ! class_exists('EllisLab\ExpressionEngine\Updater\Service\Updater\Runner'))
		{
			$this->loadMicroapp();
		}

		$runner = new \EllisLab\ExpressionEngine\Updater\Service\Updater\Runner();

		if ( ! $step ) {

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
		defined('PATH_CACHE') || define('PATH_CACHE',  SYSPATH . 'user/cache/');
		defined('PATH_THIRD') || define('PATH_THIRD',  SYSPATH . 'user/addons/');
		defined('APP_VER') || define('APP_VER', $this->currentVersion);
		defined('IS_CORE') ||define('IS_CORE', FALSE);
		defined('DOC_URL') || define('DOC_URL', 'https://docs.expressionengine.com/v5/');

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
		$databaseConfig = ee()->config->item('database');

		ee()->load->database();
		ee()->db->swap_pre = 'exp_';
		ee()->db->dbprefix = $databaseConfig['expressionengine']['dbprefix'] ?? 'exp_';
		ee()->db->db_debug = FALSE;

		ee()->load->add_package_path(EE_APPPATH);
		ee()->load->library('functions');
		ee()->load->library('extensions');
		ee()->load->library('api');
		ee()->load->library('localize');
		ee()->load->helper('language');
		ee()->lang->loadfile('installer');
		ee()->load->library('progress');
		ee()->load->model('installer_template_model', 'template_model');

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
		$this->autoload(SYSPATH . 'ee/updater/EllisLab/ExpressionEngine/Updater/Library/');
		$this->autoload(SYSPATH . 'ee/updater/EllisLab/ExpressionEngine/Updater/Service/Logger/');
		$this->autoload(SYSPATH . 'ee/installer/updates/');

		require_once SYSPATH . 'ee/updater/EllisLab/ExpressionEngine/Updater/Service/Updater/SteppableTrait.php';
		require_once SYSPATH . 'ee/updater/EllisLab/ExpressionEngine/Updater/Service/Updater/Logger.php';
		require_once SYSPATH . 'ee/updater/EllisLab/ExpressionEngine/Updater/Service/Updater/Verifier.php';
		require_once SYSPATH . 'ee/updater/EllisLab/ExpressionEngine/Updater/Service/Updater/FileUpdater.php';
		require_once SYSPATH . 'ee/updater/EllisLab/ExpressionEngine/Updater/Service/Updater/DatabaseUpdater.php';
		require_once SYSPATH . 'ee/updater/EllisLab/ExpressionEngine/Updater/Service/Updater/Runner.php';
	}

	/**
	 * autoload directories for microapp
	 * @param  string $dir
	 * @return void
	 */
	private function autoload($dir)
	{

	    foreach ( scandir( $dir ) as $file ) {

	    	if ( is_dir( $dir . $file ) && substr( $file, 0, 1 ) == '.' ) {
	    		continue;
	    	}
			if ( is_dir( $dir . $file ) ) {
				$this->autoload( $dir . $file . '/' );
			}

			// php file?
			if (
				substr( $file, 0, 2 ) !== '._'
				&& preg_match( "/.php$/i" , $file ))
			{
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

		if($this->option('--force-addon-upgrades', false)) {

			$this->write("<<red>>You have indicated you want to upgrade all addons.<<reset>>");

			if($this->defaultToYes || $this->confirm('Are you sure? This may be a destructive action.') ) {

				defined('CLI_UPDATE_FORCE_ADDON_UPDATE') || define('CLI_UPDATE_FORCE_ADDON_UPDATE', $this->option('--force-addon-upgrades', false));

			} else {

				$this->write('<<red>>Addon update halted<<reset>>');

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

		if(! class_exists('Wizard')) {
			return $this->getUpgradeVersionFromCurl();
		}

		$wizard = get_class_vars('Wizard');

		$this->info('Getting upgrade information from your local environment');

		$this->updateType = 'local';
		$this->updateVersion = $wizard['version'];

	}

	private function getUpgradeVersionFromCurl()
	{

		$this->info('Getting upgrade information from ExpressionEngine.com');

		ee()->load->library('el_pings');

		$version_file = ee()->el_pings->get_version_info(true);

		$this->updateType == 'curl';
		$this->updateVersion = $version_file['latest_version'];

	}

	protected function runUpgrade()
	{

		return $this->updateType == 'local'
				? $this->upgradeFromLocalVersion()
				: $this->upgradeFromDownloadedVersion();

	}

	protected function upgradeFromLocalVersion()
	{

		// We have to initialize differently for local files
		require_once SYSPATH . 'ee/installer/updater/EllisLab/ExpressionEngine/Updater/Service/Updater/SteppableTrait.php';

		$this->autoload(SYSPATH . 'ee/installer/updates/');

		ee()->load->library('session');
		ee()->load->library('smartforge');
		ee()->load->library('logger');
		ee()->load->library('update_notices');
		define('PATH_TMPL',   SYSPATH.'user/templates/');
		defined('USERNAME_MAX_LENGTH') || define('USERNAME_MAX_LENGTH', 75);
		defined('PASSWORD_MAX_LENGTH') || define('PASSWORD_MAX_LENGTH', 72);
		defined('URL_TITLE_MAX_LENGTH') || define('URL_TITLE_MAX_LENGTH', 200);
		defined('PATH_CACHE') || define('PATH_CACHE',  SYSPATH.'user/cache/');
		defined('PATH_TMPL') || define('PATH_TMPL',   SYSPATH.'user/templates/');
		defined('DOC_URL') || define('DOC_URL', 'https://docs.expressionengine.com/v5/');

		// Load versions of EE
		$upgradeMap = UpgradeMap::$versionsSupported;

		$next_version = $this->currentVersion;

		// For early version 2, we didn't use dots. 
		if (strpos($next_version, '.') == false) {
		    $next_version = implode('.', str_split($next_version, 1));
		}

		$currentVersionKey = array_search($next_version, $upgradeMap);

		$end_version = $this->updateVersion;

		// This will loop through all versions of EE
		do {

			$this->info('Updating to version ' . $next_version);

			// Instantiate the updater class
			if (class_exists('Updater')) {
				$UD = new Updater;
			} else {
				$class = '\EllisLab\ExpressionEngine\Updater\Version_' . str_replace('.', '_', $next_version) . '\Updater';
				$UD = new $class;
			}

			if (($status = $UD->do_update()) === false) {
				$this->fail('Failed on version ' . $next_version);
			}

			$currentVersionKey--;

			ee()->config->set_item('app_version', $upgradeMap[$currentVersionKey]);
			ee()->config
					->_update_config([
								'app_version'	=> $upgradeMap[$currentVersionKey]
					]);

			$next_version = $upgradeMap[$currentVersionKey];

		} while (version_compare($next_version, $end_version, '<'));

		// Complete upgrades
		UpgradeUtility::run();

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

}