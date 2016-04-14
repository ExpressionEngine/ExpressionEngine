<?php

namespace EllisLab\ExpressionEngine\Service\Updater;

use EllisLab\ExpressionEngine\Service\Updater\UpdaterException;
use EllisLab\ExpressionEngine\Library\Filesystem\Filesystem;
use EllisLab\ExpressionEngine\Library\Curl\RequestFactory;
use EllisLab\ExpressionEngine\Service\Config\File;
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

	protected $filename = 'ExpressionEngine.zip';
	protected $extracted_folder = 'ExpressionEngine';
	protected $manifest_location = 'system/ee/installer/updater/hash-manifest';

	/**
	 * Constructor
	 *
	 * @param	string				$license_number	License number to send along
	 * 	with the payload request
	 * @param	string				$payload_url	URL to request payload from
	 * @param	Curl\RequestFactory	$curl			cURL service object
	 * @param	Filesystem			$filesystem		Filesystem service object
	 * @param	ZipArchive			$zip_archive	PHP-native ZipArchive object
	 * @param	Config\File			$config			File config service object
	 */
	public function __construct($license_number, $payload_url, RequestFactory $curl, Filesystem $filesystem, ZipArchive $zip_archive, File $config, Verifier $verifier, Logger $logger)
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

		// Attempt to set time and memory limits
		@set_time_limit(0);
		@ini_set('memory_limit','256M');
	}

	/**
	 * Performs all steps to download, extract, and verify the update in succession,
	 * meant for CLI use
	 */
	public function getUpdateFiles()
	{
		$steps = $this->getSteps();

		foreach ($steps as $step)
		{
			$this->$step();
		}
	}

	/**
	 * Gets update steps separated in chunks to be performed in AJAX requests
	 */
	public function getSteps()
	{
		return array(
			'preflight',
			'downloadPackage',
			'unzipPackage',
			'verifyExtractedPackage',
			// TODO: Check requirements of new version here?
			'moveUpdater'
		);
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

		if ( ! $this->filesystem->isWritable(SYSPATH.'ee/'))
		{
			throw new UpdaterException('system/ee folder not writable.', 2);
		}

		if ( ! $this->filesystem->isWritable(PATH_THEMES))
		{
			throw new UpdaterException('themes/ee folder not writable.', 3);
		}
	}

	/**
	 * Verifies we have permission to write to the folders we need to to complete
	 * the upgrade
	 */
	protected function takeSiteOffline()
	{
		$this->logger->log('Taking the site offline');
		$this->config->set('is_site_on', 'n');
	}

	/**
	 * Performs the actual download of the update package and verifies its integrity
	 */
	public function downloadPackage()
	{
		$this->logger->log('Downloading update package');

		$curl = $this->curl->post(
			$this->payload_url,
			array('license' => $this->license_number)
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
	}

	/**
	 * Moves the update package into position to be executed and finish the upgrade
	 */
	public function moveUpdater()
	{
		$source = $this->getExtractedArchivePath().'/system/ee/installer/updater';

		$this->filesystem->rename($source, SYSPATH.'ee/updater');

		$this->verifier->verifyPath(
			SYSPATH . '/ee/updater',
			SYSPATH . '/ee/updater/hash-manifest',
			'system/ee/installer/updater'
		);
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
