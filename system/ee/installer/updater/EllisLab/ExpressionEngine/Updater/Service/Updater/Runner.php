<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Updater\Service\Updater;

use EllisLab\ExpressionEngine\Updater\Library\Filesystem\Filesystem;
use EllisLab\ExpressionEngine\Updater\Service;
use EllisLab\ExpressionEngine\Updater\Service\Updater\Logger;

/**
 * Handles the bulk of the upgrade process, namely backing up the files and
 * database, and updating the files and database
 */
class Runner {
	use Service\Updater\SteppableTrait {
		runStep as runStepParent;
	}

	protected $logger;

	public function __construct()
	{
		$this->setSteps([
			'updateFiles',
			'checkForDbUpdates',
			'backupDatabase',
			'updateDatabase'
		]);

		$this->logger = $this->makeLoggerService();
	}

	/**
	 * Updates this install's ExpressionEngine files to the new ones
	 */
	public function updateFiles()
	{
		$this->makeUpdaterService()->updateFiles();
	}

	/**
	 * Check to see if there are any database updates that warrant creating a
	 * backup of the database
	 */
	public function checkForDbUpdates()
	{
		$db_updater = $this->makeDatabaseUpdaterService();
		$affected_tables = $db_updater->getAffectedTables();

		if (empty($affected_tables))
		{
			return $this->setNextStep('updateDatabase');
		}

		$this->makeLoggerService()
			->log('Backing up tables: '.implode(', ', $affected_tables));
	}

	/**
	 * Makes a partial database backup based on which tables the update files
	 * have marked as being affected
	 */
	public function backupDatabase($table_name = NULL, $offset = 0)
	{
		$db_updater = $this->makeDatabaseUpdaterService();
		$affected_tables = $db_updater->getAffectedTables();
		$working_file = PATH_CACHE.'ee_update/database_backing_up.sql';
		$logger = $this->makeLoggerService();

		$backup = ee('Database/Backup', $working_file);
		$backup->makeCompactFile();

		$dbprefix = ee('Database')->getConfig()->get('dbprefix');
		$affected_tables = array_map(function($table) use ($dbprefix) {
			return $dbprefix.$table;
		}, $affected_tables);

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

			return $this->setNextStep(
				sprintf(
					'backupDatabase[%s,%s]',
					$returned['table_name'],
					$returned['offset']
				)
			);
		}

		$backup->endFile();

		// Rename this file so that we know it's a complete backup
		$filesystem = new Filesystem();
		$destination = PATH_CACHE.'ee_update/database.sql';
		if ($filesystem->isFile($destination))
		{
			$filesystem->delete($destination);
		}
		$filesystem->rename($working_file, $destination);

		$logger->log('Database backup complete: ' . $destination);

		$this->setNextStep('updateDatabase');
	}

	/**
	 * Performs database updates (the ud_x_xx_xx.php files)
	 */
	public function updateDatabase($step = NULL)
	{
		ee()->config->config['allow_extensions'] = 'n';

		$db_updater = $this->makeDatabaseUpdaterService();

		if ($db_updater->hasUpdatesToRun())
		{
			ee()->load->library('smartforge');

			$step = $step ?: $db_updater->getFirstStep();

			$log_message = 'Running database update step: ' . $step;
			$this->makeLoggerService()->log($log_message);

			// Legacy logger lib to log versions to update_log table
			ee()->logger->updater($log_message);

			$db_updater->runStep($step);

			if ($db_updater->getNextStep())
			{
				return $this->setNextStep(
					sprintf('updateDatabase[%s]', $db_updater->getNextStep())
				);
			}
		}

		ee('Filesystem')->deleteDir(SYSPATH.'ee/installer');

		$this->setNextStep('selfDestruct');
	}

	/**
	 * Rolls back the installation's files to the backups, and optionally kicks
	 * off a database restore if a backup file exists
	 */
	public function rollback()
	{
		$file_updater = $this->makeUpdaterService();
		$filesystem = new Filesystem();
		$backup_path = $file_updater->getBackupsPath() . 'system_ee/';

		// See if there are backups to restore in case we are attempting to
		// rollback after a failed database restore, which happens AFTER files
		// have already been rolled back
		if ($filesystem->isDir($backup_path) &&
			count($filesystem->getDirectoryContents($backup_path)))
		{
			$file_updater->rollbackFiles();
		}

		if (file_exists(PATH_CACHE.'ee_update/database.sql'))
		{
			return $this->setNextStep('restoreDatabase');
		}

		$this->setNextStep('selfDestruct[rollback]');
	}

	/**
	 * Restore database from backup
	 */
	public function restoreDatabase()
	{
		$db_path = PATH_CACHE.'ee_update/database.sql';
		$this->logger->log('Importing SQL from backup: ' . $db_path);

		ee('Database/Restore')->restoreLineByLine($db_path);

		$this->setNextStep('selfDestruct[rollback]');
	}

	/**
	 * Cleans up and supporting update files, turns the system back on, and
	 * updates app_version
	 */
	public function selfDestruct($rollback = NULL)
	{
		$config = ee('Config')->getFile();
		$config->set('is_system_on', 'y', TRUE);
		$config->set('app_version', APP_VER, TRUE);

		// Legacy logger lib to log to update_log table
		ee()->load->library('logger');
		ee()->logger->updater('Update complete. Now running version ' . APP_VER);

		$working_dir = $this->makeUpdaterService()->path();
		$this->logger->log('Deleting updater working directory: ' . $working_dir);
		ee('Filesystem')->deleteDir($working_dir);

		ee('Filesystem')->deleteDir(SYSPATH.'ee/updater');

		if (REQ == 'CLI')
		{
			stdout('Successfully updated to ExpressionEngine ' . APP_VER, CLI_STDOUT_SUCCESS);
		}

		ee()->config->config['allow_extensions'] = 'n';

		ee()->lang->loadfile('updater');
		ee()->load->library('session');

		if (empty($rollback))
		{
			ee()->session->set_flashdata('update:completed', TRUE);
		}
		else
		{
			ee('CP/Alert')->makeBanner('update-rolledback')
				->asWarning()
				->withTitle(sprintf(lang('update_rolledback'), APP_VER))
				->addToBody(sprintf(
					lang('update_rolledback_desc'),
					DOC_URL.'installation/update.html'
				))
				->defer();
		}
	}

	/**
	 * Overrides SteppableTrait's runStep to be a catch-all for exceptions
	 */
	public function runStep($step)
	{
		$message = $this->getLanguageForStep($step);
		if (REQ == 'CLI' && ! empty($message) && strpos($step, '[') === FALSE)
		{
			stdout($message.'...', CLI_STDOUT_BOLD);
		}

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

	/**
	 * Gets status language for a given step of the update process, this
	 * language is used to display status for both GUI and CLI updates
	 */
	public function getLanguageForStep($step)
	{
		if ($step)
		{
			if (strpos($step, 'backupDatabase') === 0)
			{
				$step = 'backupDatabase';
			}
			elseif (strpos($step, 'updateDatabase') === 0)
			{
				$step = 'updateDatabase';
			}
		}

		$messages = [
			'updateFiles' => 'Updating files',
			'backupDatabase' => 'Backing up database',
			'updateDatabase' => 'Running updates',
			'rollback' => 'Rolling back install',
			'restoreDatabase' => 'Restoring database',
			'selfDestruct' => 'Cleaning up',
		];

		return isset($messages[$step]) ? $messages[$step] : '';
	}

	/**
	 * Makes DatabaseUpdater service
	 */
	protected function makeDatabaseUpdaterService()
	{
		return new Service\Updater\DatabaseUpdater(
			ee()->config->item('app_version'),
			new Filesystem()
		);
	}

	/**
	 * Makes FileUpdater service
	 */
	protected function makeUpdaterService()
	{
		$filesystem = new Filesystem();
		$verifier = new Service\Updater\Verifier($filesystem);

		return new Service\Updater\FileUpdater(
			$filesystem,
			$verifier,
			$this->logger
		);
	}

	/**
	 * Makes Updater\Logger service
	 */
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
