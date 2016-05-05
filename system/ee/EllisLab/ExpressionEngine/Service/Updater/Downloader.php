<?php

namespace EllisLab\ExpressionEngine\Service\Updater;

use EllisLab\ExpressionEngine\Service\Updater\UpdaterException;
use EllisLab\ExpressionEngine\Library\Filesystem\Filesystem;
use EllisLab\ExpressionEngine\Library\Curl\RequestFactory;
use EllisLab\ExpressionEngine\Service\Config\File;
use EllisLab\ExpressionEngine\Library\Data\Collection;
use ZipArchive;

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
 * ExpressionEngine Updater Downloader class
 *
 * @package		ExpressionEngine
 * @subpackage	Updater
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Downloader {

	protected $license_number = '';
	protected $payload_url = '';
	protected $curl = NULL;
	protected $filesystem = NULL;
	protected $zip_archive = NULL;
	protected $config = NULL;
	protected $verifier = NULL;
	protected $logger = NULL;
	protected $requirements = NULL;
	protected $sites = NULL;

	protected $filename = 'ExpressionEngine.zip';
	protected $extracted_folder = 'ExpressionEngine';
	protected $manifest_location = 'system/ee/installer/updater/hash-manifest';

	/**
	 * Constructor
	 *
	 * @param	string				$license_number	License number to send along
	 * 	with the payload request
	 * @param	string						$payload_url	URL to request payload from
	 * @param	Curl\RequestFactory			$curl			cURL service object
	 * @param	Filesystem					$filesystem		Filesystem service object
	 * @param	ZipArchive					$zip_archive	PHP-native ZipArchive object
	 * @param	Config\File					$config			File config service object
	 * @param	Verifier					$verifier		File verifier object
	 * @param	Logger						$logger			Updater logger object
	 * @param	RequirementsCheckerLoader	$requirements	Requirements checker loader object
	 * @param	Collection					$sites			Collection of all the Site model objects
	 */
	public function __construct($license_number, $payload_url, RequestFactory $curl, Filesystem $filesystem, ZipArchive $zip_archive, File $config, Verifier $verifier, Logger $logger, RequirementsCheckerLoader $requirements, Collection $sites)
	{
		if (empty($license_number) OR ! is_string($license_number))
		{
			throw new UpdaterException('Invalid license number argument.');
		}

		if (empty($payload_url) OR ! is_string($payload_url))
		{
			throw new UpdaterException('Invalid URL argument.');
		}

		$this->license_number = $license_number;
		$this->payload_url = $payload_url;
		$this->curl = $curl;
		$this->filesystem = $filesystem;
		$this->zip_archive = $zip_archive;
		$this->config = $config;
		$this->verifier = $verifier;
		$this->logger = $logger;
		$this->requirements = $requirements;
		$this->sites = $sites;

		// Attempt to set time and memory limits
		@set_time_limit(0);
		@ini_set('memory_limit','256M');
	}

	/**
	 * Performs all steps to download, extract, and verify the update in succession,
	 * meant for CLI use
	 */
	public function getUpdate()
	{
		$step = $this->preflight();

		while ($step !== FALSE)
		{
			$step = $this->$step();
		}
	}

	/**
	 * Preflight checks such as checking permissions, taking the site offline,
	 * and cleaning up past update attempts
	 */
	public function preflight()
	{
		$this->logger->log('Maximum execution time: '.@ini_get('max_execution_time'));
		$this->logger->log('Memory limit: '.@ini_get('memory_limit'));

		$this->cleanUpOldUpgrades();
		$this->checkDiskSpace();
		$this->checkPermissions();
		$this->takeSiteOffline();

		return 'downloadPackage';
	}

	/**
	 * Cleans up download and extract locations of any previous update artifacts
	 */
	protected function cleanUpOldUpgrades()
	{
		$this->logger->log('Cleaning up upgrade working directory');

		// TODO: Check to see if we even have permission to do these things

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
	}

	/**
	 * Verifies we have enough disk space to download and extract the package
	 */
	protected function checkDiskSpace()
	{
		$this->logger->log('Checking free disk space');
		$free_space = $this->filesystem->getFreeDiskSpace($this->path());

		// Try to maintain at least 50MB free disk space
		if ($free_space < 52428800)
		{
			$this->cleanUpOldUpgrades();
			throw new UpdaterException('Not enough disk space available to complete the update ('.$free_space.' free bytes reported). Please free up some space and try the upgrade again.', 11);
		}
	}

	/**
	 * Verifies we have permission to write to the folders we need to to complete
	 * the upgrade
	 */
	protected function checkPermissions()
	{
		$this->logger->log('Checking file permissions needed to complete the update');

		if ( ! $this->filesystem->isWritable($this->path()))
		{
			throw new UpdaterException('Cache folder not writable.', 1);
		}

		// system/ee
		if ( ! $this->filesystem->isWritable(SYSPATH.'ee/'))
		{
			throw new UpdaterException('system/ee folder not writable.', 2);
		}

		// Contents of system/ee
		foreach ($this->filesystem->getDirectoryContents(SYSPATH.'ee/') as $path)
		{
			if ( ! $this->filesystem->isWritable($path))
			{
				throw new UpdaterException('Path not writable: ' . $path, 15);
			}
		}

		// Theme paths for each site
		foreach ($this->getThemePaths() as $path)
		{
			$theme_path = rtrim($path, DIRECTORY_SEPARATOR).'/ee/';

			if ( ! $this->filesystem->isWritable($theme_path))
			{
				throw new UpdaterException('Path not writable: ' . $path, 3);
			}

			// Theme path folder contents
			foreach ($this->filesystem->getDirectoryContents($theme_path) as $path)
			{
				if ( ! $this->filesystem->isWritable($path))
				{
					throw new UpdaterException('Path not writable: ' . $path, 16);
				}
			}
		}
	}

	/**
	 * Verifies we have permission to write to the folders we need to to complete
	 * the upgrade
	 */
	protected function takeSiteOffline()
	{
		$this->logger->log('Taking the site offline');

		// TODO: This isn't actually writing to the file
		$this->config->set('is_system_on', 'n');
	}

	/**
	 * Performs the actual download of the update package and verifies its integrity
	 */
	public function downloadPackage()
	{
		$this->logger->log('Downloading update package');

		$curl = $this->curl->post(
			$this->payload_url,
			['license' => $this->license_number]
		);

		$data = $curl->exec();

		// Make sure everything looks normal
		if ($curl->getHeader('http_code') != '200')
		{
			throw new UpdaterException('Could not download update. Status code: ' . $curl->getHeader('http_code'), 4);
		}

		if (trim($curl->getHeader('Content-Type'), '"') != 'application/zip')
		{
			throw new UpdaterException('Could not download update. Unexpected MIME type response: ' . $curl->getHeader('Content-Type'), 5);
		}

		if ( ! $curl->getHeader('Package-Hash'))
		{
			throw new UpdaterException('Could not find hash header to verify zip archive integrity.', 6);
		}

		// Write the file
		$this->filesystem->write($this->getArchiveFilePath(), $data, TRUE);

		// Grab the zip's SHA1 hash to verify integrity
		$hash = $this->filesystem->sha1File($this->getArchiveFilePath());

		// Make sure the file's SHA1 matches what we were given in the header
		if (trim($curl->getHeader('Package-Hash'), '"') != $hash)
		{
			throw new UpdaterException('Could not verify zip archive integrity. Given hash ' . $curl->getHeader('Package-Hash') . ' does not match ' . $hash, 7);
		}

		return 'unzipPackage';
	}

	/**
	 * Unzips package to extract folder
	 */
	public function unzipPackage()
	{
		$this->logger->log('Unzipping package');

		$this->filesystem->mkDir($this->getExtractedArchivePath());

		if (($response = $this->zip_archive->open($this->getArchiveFilePath())) === TRUE)
		{
			$this->zip_archive->extractTo($this->getExtractedArchivePath());
			$this->zip_archive->close();
			$this->logger->log('Package unzipped');
		}
		else
		{
			throw new UpdaterException('Could not unzip update archive. ZipArchive error code: ' . $response, 8);
		}

		return 'verifyExtractedPackage';
	}

	/**
	 * Goes through each file in the extracted archive to verify unzip integrity
	 */
	public function verifyExtractedPackage()
	{
		$this->logger->log('Verifying integrity of unzipped package');

		$extracted_path = $this->getExtractedArchivePath();

		$this->verifier->verifyPath($extracted_path, $extracted_path . '/' . $this->manifest_location);

		$this->logger->log('Package contents successfully verified');

		return 'checkRequirements';
	}

	/**
	 * Check server requirements for the new update before we bother doing anything else
	 */
	public function checkRequirements()
	{
		$this->logger->log('Checking server requirements of new ExpressionEngine version');

		$this->requirements->setClassPath(
			$this->getExtractedArchivePath().'/system/ee/installer/updater/EllisLab/ExpressionEngine/Service/Updater/RequirementsChecker.php'
		);
		$result = $this->requirements->check();

		if ($result !== TRUE)
		{
			$failed = array_map(function($requirement) {
				return $requirement->getMessage();
			}, $result);

			throw new UpdaterException("Your server has failed the requirements for this version of ExpressionEngine: \n" . implode("\n", $failed), 14);
		}

		$this->logger->log('SUCCESS: Server requirements check completed');

		return 'moveUpdater';
	}

	/**
	 * Moves the update package into position to be executed and finish the upgrade
	 */
	public function moveUpdater()
	{
		$this->stashConfigs();

		$this->logger->log('Moving the updater micro app into place');

		$source = $this->getExtractedArchivePath().'/system/ee/installer/updater';

		$this->filesystem->rename($source, SYSPATH.'ee/updater');

		try {
			$this->verifier->verifyPath(
				SYSPATH . '/ee/updater',
				SYSPATH . '/ee/updater/hash-manifest',
				'system/ee/installer/updater'
			);
		}
		catch (UpdaterException $e)
		{
			// Remove the updater
			$this->filesystem->deleteDir(SYSPATH.'ee/updater');
			throw new UpdaterException($e->getMessage(), $e->getCode());
		}

		// No further steps needed, Updater app will take over
		return FALSE;
	}

	/**
	 * Here, we need to gather all the information the updater microapp might need,
	 * such as file paths. We may not be able to access these things easily from
	 * within the microapp because they may be stored in any manner of places, so
	 * we'll grab them early and put them in our working directory for the update.
	 */
	protected function stashConfigs()
	{
		$configs = [
			'update_path' => $this->path(),
			'archive_path' => $this->getExtractedArchivePath(),
			'theme_paths' => array_unique(
				$this->getThemePaths()
			)
		];

		$this->filesystem->write(
			$this->path() . 'configs.json',
			json_encode($configs),
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

		return $theme_paths;
	}

	/**
	 * Constructs and returns the path to the downloaded zip archive
	 *
	 * @return	string	Path to downloaded zip archive
	 */
	protected function getArchiveFilePath()
	{
		return $this->path() . $this->filename;
	}

	/**
	 * Constructs and returns the path to the extracted archive path
	 *
	 * @return	string	Path to extracted archive
	 */
	protected function getExtractedArchivePath()
	{
		return $this->path() . $this->extracted_folder;
	}

	/**
	 * Optionally creates and returns the path in which we will be working with
	 * our files
	 *
	 * @return	string	Path to folder in the cache folder for working with updates
	 */
	protected function path()
	{
		$cache_path = $this->config->get('cache_path');

		if (empty($cache_path))
		{
			$cache_path = SYSPATH.'user'.DIRECTORY_SEPARATOR.'cache/';
		}

		$cache_path .= 'ee_update/';

		if ( ! is_dir($cache_path))
		{
			$this->filesystem->mkDir($cache_path);
		}

		return $cache_path;
	}
}

// EOF
