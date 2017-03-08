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
	use Service\Updater\Steppable {
		runStep as runStepParent;
	}

	// The idea here is to separate the download and unpacking
	// process into quick, hopefully low-memory tasks when accessed
	// through the browser
	protected $steps = [
		'updateFiles',
		'checkForDbUpdates',
		'backupDatabase',
		'updateDatabase',
		'selfDestruct'
	];

	protected $logger;

	public function __construct()
	{
		$this->logger = $this->makeLoggerService();
	}

	public function updateFiles()
	{
		$this->makeUpdaterService()->updateFiles();
	}

	public function checkForDbUpdates()
	{
		$db_updater = $this->makeDatabaseUpdaterService();
		$affected_tables = $db_updater->getAffectedTables();

		if (empty($affected_tables))
		{
			return 'updateDatabase';
		}

		$this->makeLoggerService()
			->log('Backing up tables: '.implode(', ', $affected_tables));
	}

	public function backupDatabase($table_name = NULL, $offset = 0)
	{
		$db_updater = $this->makeDatabaseUpdaterService();
		$affected_tables = $db_updater->getAffectedTables();
		$working_file = PATH_CACHE.'ee_update/database_backing_up.sql';
		$logger = $this->makeLoggerService();

		$backup = ee('Database/Backup', $working_file);
		$backup->makeCompactFile();
		$backup->setTablesToBackup($affected_tables);

		if (empty($table_name))
		{
			$logger->log('Starting database backup to file: ' . $working_file);

			$backup->startFile();
			$backup->writeDropAndCreateStatements();
		}

		$returned = $backup->writeTableInsertsConservatively($table_name, $offset);

		// Backup not finished? Start a new request with the table name and
		// offset to start from
		if ($returned !== FALSE)
		{
			$logger->log('Continuing backup at table '.$table_name.', offset '.$offset);

			return sprintf('backupDatabase[%s,%s]', $returned['table_name'], $returned['offset']);
		}

		// Rename this file so that we know it's a complete backup
		$filesystem = new Filesystem();
		$destination = PATH_CACHE.'ee_update/database.sql';
		if ($filesystem->isFile($destination))
		{
			$filesystem->delete($destination);
		}
		$filesystem->rename($working_file, $destination);

		$logger->log('Database backup complete: ' . $destination);

		return 'updateDatabase';
	}

	public function updateDatabase($step = NULL)
	{
		$db_updater = $this->makeDatabaseUpdaterService();

		if ($db_updater->hasUpdatesToRun())
		{
			ee()->load->library('smartforge');

			$step = $step ?: $db_updater->getFirstStep();

			$this->makeLoggerService()
				->log('Running database update step: ' . $step);

			$db_updater->runStep($step);

			if ($db_updater->getNextStep())
			{
				return sprintf('updateDatabase[%s]', $db_updater->getNextStep());
			}
		}

		return 'rollback';
	}

	public function rollback()
	{
		$this->makeUpdaterService()->rollbackFiles();

		if (file_exists(PATH_CACHE.'ee_update/database.sql'))
		{
			return 'restoreDatabase';
		}

		return 'selfDestruct';
	}

	public function restoreDatabase()
	{
		ee('Database/Restore')->restoreLineByLine(PATH_CACHE.'ee_update/database.sql');

		return 'selfDestruct';
	}

	public function selfDestruct()
	{
		$config = ee('Config')->getFile();
		$config->set('is_system_on', 'y', TRUE);
		$config->set('app_version', APP_VER, TRUE);

		ee('Filesystem')->deleteDir($this->makeUpdaterService()->path());
		ee('Filesystem')->deleteDir(SYSPATH.'ee/updater');
	}

	public function runStep($step)
	{
		try
		{
			$this->runStepParent($step);
		}
		catch (\Exception $e)
		{
			$this->logger->log($e->getMessage());
			$this->logger->log($e->getTraceAsString());

			throw $e;
		}
	}

	protected function makeDatabaseUpdaterService()
	{
		return new Service\Updater\DatabaseUpdater(
			ee()->config->item('app_version'),
			new Filesystem()
		);
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

		return new Service\Updater\FileUpdater(
			$filesystem,
			$config,
			$verifier,
			$this->logger
		);
	}

	protected function makeLoggerService()
	{
		return new Service\Updater\Logger(
			PATH_CACHE.'ee_update/update.log',
			new Filesystem(),
			php_sapi_name() === 'cli'
		);
	}
}
// EOF
