<?php

namespace EllisLab\ExpressionEngine\Service\Updater;

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
	use Steppable;

	// The idea here is to separate the download and unpacking
	// process into quick, hopefully low-memory tasks when accessed
	// through the browser
	protected $steps = [
		'preflight',
		'download',
		'unpack'
	];

	// Downloader singleton
	protected $downloader;

	/**
	 * @param	Downloader	$downloader	Updater downloader object
	 */
	public function __construct(Downloader $downloader)
	{
		$this->downloader = $downloader;
	}

	public function preflight()
	{
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
}
// EOF
