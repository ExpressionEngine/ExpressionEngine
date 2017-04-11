<?php

namespace EllisLab\ExpressionEngine\Service\Updater;

use EllisLab\ExpressionEngine\Service\Updater\UpdaterException;
use EllisLab\ExpressionEngine\Service\License\ExpressionEngineLicense;
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

	protected $license;
	protected $payload_url;
	protected $curl;
	protected $filesystem;
	protected $zip_archive;
	protected $config;
	protected $verifier;
	protected $logger;
	protected $requirements;
	protected $sites;

	protected $filename = 'ExpressionEngine.zip';
	protected $extracted_folder = 'ExpressionEngine';
	protected $manifest_location = 'system/ee/installer/updater/hash-manifest';

	/**
	 * Constructor
	 *
	 * @param	ExpressionEngineLicense		$license		ExpressionEngineLicense object
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
	public function __construct(ExpressionEngineLicense $license, $payload_url, RequestFactory $curl, Filesystem $filesystem, ZipArchive $zip_archive, File $config, Verifier $verifier, Logger $logger, RequirementsCheckerLoader $requirements, Collection $sites)
	{
		$this->license = $license;
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
	 * Preflight checks such as checking permissions, taking the site offline,
	 * and cleaning up past update attempts
	 */
	public function preflight()
	{
		$this->logger->log('Maximum execution time: '.@ini_get('max_execution_time'));
		$this->logger->log('Memory limit: '.@ini_get('memory_limit'));

		$this->checkPermissions();
		$this->cleanUpOldUpgrades();
		$this->checkDiskSpace();
	}

	/**
	 * Cleans up download and extract locations of any previous update artifacts
	 */
	protected function cleanUpOldUpgrades()
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
	protected function checkDiskSpace()
	{
		$this->logger->log('Checking free disk space');
		$free_space = $this->filesystem->getFreeDiskSpace($this->path());
		$this->logger->log('Free disk space (bytes): '.$free_space);

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
				'https://docs.expressionengine.com/latest/installation/update.html'
			), 1);
		}
	}

	/**
	 * Verifies we have permission to write to the folders we need to to complete
	 * the upgrade
	 */
	protected function takeSiteOffline()
	{
		$this->logger->log('Taking the site offline');

		$this->config->set('is_system_on', 'n', TRUE);
	}

	/**
	 * Performs the actual download of the update package and verifies its integrity
	 */
	public function downloadPackage()
	{
		$this->logger->log('Downloading update package');

		$curl = $this->curl->post(
			$this->payload_url,
			[
				'action' => 'download_update',
				'license' => $this->license->getRawLicense(),
				'version' => APP_VER
			]
		);

		$data = $curl->exec();

		// Make sure everything looks normal
		if ($curl->getHeader('http_code') != '200')
		{
			throw new UpdaterException(
				sprintf(lang('could_not_download')."\n\n".lang('try_again_later'),
				$curl->getHeader('http_code')),
			4);
		}

		if (trim($curl->getHeader('Content-Type'), '"') != 'application/zip')
		{
			throw new UpdaterException(
				sprintf(lang('unexpected_mime')."\n\n".lang('try_again_later'),
				$curl->getHeader('Content-Type')),
			5);
		}

		if ( ! $curl->getHeader('Package-Signature'))
		{
			throw new UpdaterException( lang('missing_hash_header')."\n\n".lang('try_again_later'), 6);
		}

		// Write the file
		$this->filesystem->write($this->getArchiveFilePath(), $data, TRUE);

		// Grab the zip's SHA384 hash to verify integrity
		$hash = $this->filesystem->hashFile('sha384', $this->getArchiveFilePath());
		$signature = trim($curl->getHeader('Package-Signature'), '"');

		if ( ! $this->verifySignature($hash, $signature))
		{
			throw new UpdaterException(
				sprintf(
					lang('could_not_verify_download')."\n\n".lang('try_again_later'),
					$hash
				),
			7);
		}
	}

	/**
	 * Verifies the signature of the downloaded build
	 *
	 * @param $hash string SHA384 hash of downloaded zip file
	 * @param $signature string Base-64 encoded signature
	 * @return boolean TRUE if verified
	 */
	private function verifySignature($hash, $signature)
	{
		$signature = base64_decode($signature);

		$verified = openssl_verify(
			$hash,
			$signature,
			openssl_get_publickey('file://'.SYSPATH.'ee/EllisLab/ExpressionEngine/EllisLabUpdate.pub'),
			OPENSSL_ALGO_SHA384
		);

		return ($verified === 1);
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
			throw new UpdaterException(
				sprintf(
					lang('could_not_unzip')."\n\n".lang('try_again_later'), $response
				),
			8);
		}
	}

	/**
	 * Goes through each file in the extracted archive to verify unzip integrity
	 */
	public function verifyExtractedPackage()
	{
		$this->logger->log('Verifying integrity of unzipped package');

		$extracted_path = $this->getExtractedArchivePath();

		try {
			$this->verifier->verifyPath(
				$extracted_path,
				$extracted_path . '/' . $this->manifest_location
			);
		}
		catch (\Exception $e)
		{
			throw new UpdaterException(
				sprintf(lang('failed_verifying_extracted_archive'), $e->getMessage())."\n\n".lang('try_again_later'),
				$e->getCode()
			);
		}

		$this->logger->log('Package contents successfully verified');
	}

	/**
	 * Check server requirements for the new update before we bother doing anything else
	 */
	public function checkRequirements()
	{
		$this->logger->log('Checking server requirements of new ExpressionEngine version');

		$this->requirements->setClassPath(
			$this->getExtractedArchivePath().'/system/ee/installer/updater/EllisLab/ExpressionEngine/Updater/Service/Updater/RequirementsChecker.php'
		);
		$result = $this->requirements->check();

		if ($result !== TRUE)
		{
			$failed = array_map(function($requirement) {
				return $requirement->getMessage();
			}, $result);

			throw new UpdaterException(
				sprintf(lang('requirements_failed'), implode("\n- ", $failed)),
			14);
		}

		$this->logger->log('Server requirements check passed with flying colors');
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
		catch (\Exception $e)
		{
			// Remove the updater
			$this->filesystem->deleteDir(SYSPATH.'ee/updater');

			throw new UpdaterException(
				sprintf(lang('failed_moving_updater'), $e->getMessage())."\n\n".lang('try_again_later'),
				$e->getCode()
			);
		}

		// Got here? Take the site offline, we're ready to update
		$this->takeSiteOffline();
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
			'theme_paths' => $this->getThemePaths()
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
			$theme_paths[$site->site_id] = $site->site_system_preferences->theme_folder_path;
		}

		return array_unique($theme_paths);
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
		$cache_path = PATH_CACHE . 'ee_update/';

		if ( ! is_dir($cache_path))
		{
			$this->filesystem->mkDir($cache_path);
		}

		return $cache_path;
	}
}

// EOF
