<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Updater\Downloader;

use EllisLab\ExpressionEngine\Service\Updater\UpdaterException;
use EllisLab\ExpressionEngine\Service\License\ExpressionEngineLicense;
use EllisLab\ExpressionEngine\Library\Filesystem\Filesystem;
use EllisLab\ExpressionEngine\Service\Updater\Logger;
use EllisLab\ExpressionEngine\Service\Config\File;
use EllisLab\ExpressionEngine\Library\Data\Collection;

/**
 * Updater preflight checker
 *
 * Runs all preflight operations to check for any potential errors that may
 * occur during upgrade so that they are fixed up front
 */
class Preflight {
	use UpdaterPaths;

	protected $filesystem;
	protected $logger;
	protected $sites;
	protected $config;

	/**
	 * Constructor
	 *
	 * @param	Filesystem $filesystem Filesystem service object
	 * @param	Logger $logger Updater logger object
	 * @param	Config\File $config File config service object
	 * @param	Collection $sites Collection of all the Site model objects
	 */
	public function __construct(Filesystem $filesystem, Logger $logger, File $config, Collection $sites)
	{
		$this->filesystem = $filesystem;
		$this->logger = $logger;
		$this->sites = $sites;
		$this->config = $config;
	}

	/**
	 * Cleans up download and extract locations of any previous update artifacts
	 */
	public function cleanUpOldUpgrades()
	{
		$this->logger->log('Cleaning up upgrade working directory');

		// Delete any old zip archives
		if ($this->filesystem->isFile($this->getArchiveFilePath()))
		{
			$this->logger->log('Old zip archives found, deleting');
			$this->filesystem->delete($this->getArchiveFilePath());
		}

		// Delete old extracted archives
		if ($this->filesystem->isDir($this->getExtractedArchivePath()))
		{
			$this->logger->log('Old extracted archives found, deleting');
			$this->filesystem->delete($this->getExtractedArchivePath());
		}

		// Delete any old SQL backups
		$sql_path = $this->path().'database.sql';
		if ($this->filesystem->isFile($sql_path))
		{
			$this->logger->log('Old SQL backup found, deleting');
			$this->filesystem->delete($sql_path);
		}

		// Delete old extracted archives
		$backup_path = $this->path().'backups';
		if ($this->filesystem->isDir($backup_path))
		{
			$this->logger->log('Old file backup folders found, deleting');
			$this->filesystem->delete($backup_path);
		}
	}

	/**
	 * Verifies we have enough disk space to download and extract the package
	 */
	public function checkDiskSpace()
	{
		$this->logger->log('Checking free disk space');
		$free_space = $this->filesystem->getFreeDiskSpace($this->path());
		$this->logger->log('Free disk space (bytes): '.$free_space);

		// Try to maintain at least 50MB free disk space
		if ($free_space < 52428800)
		{
			throw new UpdaterException('Not enough disk space available to complete the update ('.$free_space.' free bytes reported). Please free up some space and try the upgrade again.', 11);
		}
	}

	/**
	 * Verifies we have permission to write to the folders we need to to complete
	 * the upgrade
	 */
	public function checkPermissions()
	{
		$this->logger->log('Checking file permissions needed to complete the update');

		$theme_paths = array_map(function($path)
		{
			return rtrim($path, DIRECTORY_SEPARATOR).'/ee/';
		}, $this->getThemePaths());

		$paths = array_merge(
			[
				$this->path(),
				SYSPATH.'ee',
				PATH_CACHE,
				SYSPATH.'user/config/config.php'
			],
			$this->filesystem->getDirectoryContents(SYSPATH.'ee/'),
			$theme_paths
		);

		foreach ($theme_paths as $path)
		{
			$paths = array_merge(
				$paths,
				$this->filesystem->getDirectoryContents($path)
			);
		}

		$paths = array_filter($paths, function($path)
		{
			return ! $this->filesystem->isWritable($path);
		});

		if ( ! empty($paths))
		{
			// This bit of code before the exception truncates the full server
			// path from the path strings and shortens them to just their parent
			// theme or system folder
			$search = array_map(function($theme_path)
			{
				$real_path = realpath($theme_path.'../../');
				return $real_path ? $real_path.'/': $theme_path;
			}, $theme_paths);

			$syspath = realpath(SYSPATH.'../');
			$search[] = $syspath ? $syspath.'/' : SYSPATH;
			$search = array_unique($search);

			$paths = array_map(function($path) use ($search)
			{
				return str_replace($search, '', $path);
			}, $paths);

			throw new UpdaterException(sprintf(
				lang('files_not_writable'),
				implode("\n", $paths),
				DOC_URL.'installation/update.html'
			), 1);
		}
	}

	/**
	 * Here, we need to gather all the information the updater microapp might need,
	 * such as file paths. We may not be able to access these things easily from
	 * within the microapp because they may be stored in any manner of places, so
	 * we'll grab them early and put them in our working directory for the update.
	 */
	public function stashConfig()
	{
		$config = [
			'update_path' => $this->path(),
			'archive_path' => $this->getExtractedArchivePath(),
			'theme_paths' => $this->getThemePaths()
		];

		$this->filesystem->write(
			$this->path() . 'configs.json',
			json_encode($config),
			TRUE
		);
	}

	/**
	 * Creates an array of theme paths for all sites
	 *
	 * @return	array	Theme server paths
	 */
	protected function getThemePaths()
	{
		// Is there a config file override for the theme path? Use that instead
		// and hope that the other sites' paths aren't conditionally set in the
		// file because we'll only get the one
		if ($this->config->get('theme_folder_path') !== NULL)
		{
			return [$this->config->get('theme_folder_path')];
		}

		$theme_paths = [];
		foreach ($this->sites as $site)
		{
			$theme_paths[] = $site->site_system_preferences->theme_folder_path;
		}

		return array_unique($theme_paths);
	}
}

// EOF
