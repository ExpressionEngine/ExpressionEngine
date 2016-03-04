<?php

namespace EllisLab\ExpressionEngine\Service\Updater;

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
 * ExpressionEngine Updater class
 *
 * @package		ExpressionEngine
 * @subpackage	Updater
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Updater {

	protected $license_number = '';
	protected $payload_url = '';
	protected $curl = NULL;
	protected $filesystem = NULL;
	protected $zip_archive = NULL;
	protected $config = NULL;

	protected $filename = 'ExpressionEngine.zip';
	protected $extracted_folder = 'ExpressionEngine';

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
	public function __construct($license_number, $payload_url, RequestFactory $curl, Filesystem $filesystem, ZipArchive $zip_archive, File $config)
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

		// TODO: Create log service?
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
			'verifyZipContents',
			'moveUpdater'
		);
	}

	/**
	 * Preflight checks such as checking permissions, taking the site offline,
	 * and cleaning up past update attempts
	 */
	public function preflight()
	{
		$this->checkPermissions();
		$this->takeSiteOffline();
		$this->cleanUpOldUpgrades();
	}

	/**
	 * Verifies we have permission to write to the folders we need to to complete
	 * the upgrade
	 */
	protected function checkPermissions()
	{
		// TODO? May need to check recursive permissions on each of these?
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
		$this->config->set('is_site_on', 'n');
	}

	/**
	 * Cleans up download and extract locations of any previous update artifacts
	 */
	protected function cleanUpOldUpgrades()
	{
		// Delete any old zip archives
		if ($this->filesystem->isFile($this->getArchiveFilePath()))
		{
			$this->filesystem->delete($this->getArchiveFilePath());
		}

		// Delete old extracted archives
		if ($this->filesystem->isDir($this->getExtractedArchivePath()))
		{
			$this->filesystem->delete($this->getExtractedArchivePath());
		}
	}

	/**
	 * Performs the actual download of the update package and verifies its integrity
	 */
	public function downloadPackage()
	{
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

		if ( ! $curl->getHeader('MD5-Hash'))
		{
			throw new UpdaterException('Could not find hash header to verify zip archive integrity.', 6);
		}

		// Write the file
		$this->filesystem->write($this->getArchiveFilePath(), $data, TRUE);

		// Grab the zip's MD5 hash to verify integrity
		$hash = $this->filesystem->md5File($this->getArchiveFilePath());

		// Make sure the file's MD5 matches what we were given in the header
		if (trim($curl->getHeader('MD5-Hash'), '"') != $hash)
		{
			throw new UpdaterException('Could not verify zip archive integrity. Given hash ' . $curl->getHeader('MD5-Hash') . ' does not match ' . $hash, 7);
		}
	}

	/**
	 * Unzips package to extract folder
	 */
	public function unzipPackage()
	{
		$this->filesystem->mkDir($this->getExtractedArchivePath());

		if (($response = $this->zip_archive->open($this->getArchiveFilePath())) === TRUE)
		{
			$this->zip_archive->extractTo($this->getExtractedArchivePath());
			$this->zip_archive->close();
		}
		else
		{
			throw new UpdaterException('Could not unzip update archive. ZipArchive error code: ' . $response, 8);
		}
	}

	/**
	 * Goes through each file in the extracted archive to verify unzip integrity
	 */
	public function verifyZipContents()
	{
		// TODO: Need a manifest inside the zip that contains MD5s of every file,
		// then use it to verify each file
	}

	/**
	 * Moves the update package into position to be executed and finish the upgrade
	 */
	public function moveUpdater()
	{
		// TODO: Move update package into place, verify its integrity, then launch
		// into the updater package; we're finished with this controller
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

use Exception;

class UpdaterException extends Exception {}

// EOF
