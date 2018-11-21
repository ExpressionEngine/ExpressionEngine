<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Updater;

use EllisLab\ExpressionEngine\Service\Updater\Logger;
use EllisLab\ExpressionEngine\Service\Updater\SteppableTrait;

/**
 * Handles the first half of an ExpressionEngine upgrade: downloading, verifying,
 * and moving the updater micro app into place
 */
class Runner {
	use SteppableTrait {
		runStep as runStepParent;
	}

	protected $logger;

	public function __construct()
	{
		// The idea here is to separate the download and unpacking
		// process into quick, hopefully low-memory tasks when accessed
		// through the browser
		$this->setSteps([
			'preflight',
			'download',
			'unpack'
		]);

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
		$preflight->stashConfig();
	}

	public function download()
	{
		ee('Updater/Downloader')->downloadPackage(
			'https://update.expressionengine.com'
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

	public function rollback()
	{
		ee('Filesystem')->deleteDir(SYSPATH.'ee/updater');
	}

	/**
	 * Catch-all exception handler for updater steps to log errors
	 */
	public function runStep($step)
	{
		if (REQ == 'CLI')
		{
			stdout($this->getLanguageForStep($step).'...', CLI_STDOUT_BOLD);
		}

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

		// We may have shifted files around
		if (function_exists('opcache_reset'))
		{
			// Check for restrict_api path restriction
			if (($opcache_api_path = ini_get('opcache.restrict_api')) && stripos(SYSPATH, $opcache_api_path) !== 0)
			{
				return;
			}

			opcache_reset();
		}
	}

	public function getLanguageForStep($step)
	{
		ee()->lang->loadfile('updater');
		return lang($step.'_step');
	}
}
// EOF
