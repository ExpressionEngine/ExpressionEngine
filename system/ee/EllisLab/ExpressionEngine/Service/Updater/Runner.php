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

	protected $downloader;
	protected $logger;

	/**
	 * @param	Downloader	$downloader	Updater downloader object
	 */
	public function __construct(Downloader $downloader, Logger $logger)
	{
		$this->downloader = $downloader;
		$this->logger = $logger;
	}

	// She packed my bags last night...
	public function preflight()
	{
		$this->logger->truncate();
		$this->downloader->preflight();
	}

	public function download()
	{
		$this->downloader->downloadPackage();
	}

	public function unpack()
	{
		$this->downloader->unzipPackage();
		$this->downloader->verifyExtractedPackage();
		$this->downloader->checkRequirements();
		$this->downloader->moveUpdater();
	}

	/**
	 * Catch-all exception handler for updater steps to log errors
	 */
	public function runStep($step)
	{
		$this->logger->stdout($this->getLanguageForStep($step).'...');

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
