<?php

namespace EllisLab\ExpressionEngine\Updater\Service\Updater;

use EllisLab\ExpressionEngine\Updater\Library\Filesystem\Filesystem;
use EllisLab\ExpressionEngine\Updater\Service;

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
	use Service\Updater\Steppable;

	// The idea here is to separate the download and unpacking
	// process into quick, hopefully low-memory tasks when accessed
	// through the browser
	protected $steps = [
		'backupDatabase',
		'updateFiles'
	];

	// File updater singleton
	protected $fileUpdater;

	public function __construct()
	{
		$this->fileUpdater = $this->makeUpdaterService();
	}

	public function backupDatabase($table_name = '', $offset = '')
	{
		// TODO: ensure this directory exists
		$backup = ee('Database/Backup', PATH_CACHE.'ee_update/database.sql');
		$backup->startFile();
		$backup->writeDropAndCreateStatements();

		/*

		if not finished {
			return 'backupDatabase[table_name,offset]';
		}
		Modify Steppable so that if a step returns a string, make that the
		actual next step, and also allow step methods to take arguments
		 */
	}

	public function updateFiles()
	{
		$this->fileUpdater->updateFiles();
	}

	/**
	 * Since we don't (yet?) have a dependency injection container, this gathers
	 * dependencies and makes the file updater service for the Runner class to use
	 */
	protected function makeUpdaterService()
	{
		$filesystem = new Filesystem();
		$config = new Service\Config\File(SYSPATH.'user/config/config.php');
		$verifier = new Service\Updater\Verifier($filesystem);
		// TODO: prolly need to put this cache path into the configs.json and load that here
		$file_logger = new Service\Logger\File(SYSPATH.'user/cache/ee_update/update.log', $filesystem, php_sapi_name() === 'cli');
		$updater_logger = new Service\Updater\Logger($file_logger);

		return new Service\Updater\FileUpdater($filesystem, $config, $verifier, $updater_logger);
	}
}
// EOF
