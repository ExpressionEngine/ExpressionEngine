<?php

namespace EllisLab\ExpressionEngine\Service\Updater;

use EllisLab\ExpressionEngine\Service\Updater\Logger;
use EllisLab\ExpressionEngine\Service\Updater\Steppable;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 4.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Updater Runner Class
 *
 * @package		ExpressionEngine
 * @subpackage	Updater
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Runner {
	use Steppable {
		runStep as runStepParent;
	}

	// The idea here is to separate the download and unpacking
	// process into quick, hopefully low-memory tasks when accessed
	// through the browser
	protected $steps = [
		'preflight',
		'download',
		'unpack'
	];

	protected $logger;

	public function __construct()
	{
		$this->logger = ee('Updater/Logger');

		// Attempt to set time and memory limits
		@set_time_limit(0);
		@ini_set('memory_limit', '256M');
	}

	// She packed my bags last night...
	public function preflight()
	{
		$this->logger->truncate();
		$this->logger->log('Maximum execution time: '.@ini_get('max_execution_time'));
		$this->logger->log('Memory limit: '.@ini_get('memory_limit'));

		$preflight = ee('Updater/Preflight');
		$preflight->checkPermissions();
		$preflight->cleanUpOldUpgrades();
		$preflight->checkDiskSpace();
		$preflight->stashConfigs();
	}

	public function download()
	{
		ee('Updater/Downloader')->downloadPackage(
			'https://expressionengine.com/index.php?ACT=269'
		);
	}

	public function unpack()
	{
		$unpacker = ee('Updater/Unpacker');
		$unpacker->unzipPackage();
		$unpacker->verifyExtractedPackage();
		$unpacker->checkRequirements();
		$unpacker->moveUpdater();

		$this->logger->log('Taking the site offline');
		ee('Config')->getFile()->set('is_system_on', 'n', TRUE);
	}

	/**
	 * Catch-all exception handler for updater steps to log errors
	 */
	public function runStep($step)
	{
		if (REQ == 'CLI') stdout($this->getLanguageForStep($step).'...', CLI_STDOUT_BOLD);

		try
		{
			$this->runStepParent($step);
		}
		catch (\Exception $e)
		{
			$this->logger->log($e->getMessage());
			$this->logger->log($e->getTraceAsString());

			// Send it up the chain
			throw $e;
		}
	}

	public function getLanguageForStep($step)
	{
		ee()->lang->loadfile('updater');
		return lang($step.'_step');
	}
}
// EOF
