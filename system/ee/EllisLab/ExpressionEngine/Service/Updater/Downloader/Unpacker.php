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

use EllisLab\ExpressionEngine\Service\Updater\Downloader\UpdaterPaths;
use EllisLab\ExpressionEngine\Service\Updater\UpdaterException;
use EllisLab\ExpressionEngine\Service\Updater\Verifier;
use EllisLab\ExpressionEngine\Service\Updater\Logger;
use EllisLab\ExpressionEngine\Service\Updater\RequirementsCheckerLoader;
use EllisLab\ExpressionEngine\Library\Filesystem\Filesystem;
use ZipArchive;

/**
 * Updater unpacker
 *
 * Unpacks the downloaded ExpressionEngine zip archive, verifies the integrity
 * of the files, checks the downloaded installation's server requirements, and
 * finally moves the micro app into place to facilitate the rest of the upgrade
 */
class Unpacker {
	use UpdaterPaths;

	protected $filesystem;
	protected $zip_archive;
	protected $verifier;
	protected $logger;
	protected $requirements;

	protected $manifest_location = 'system/ee/installer/updater/hash-manifest';

	/**
	 * Constructor
	 *
	 * @param	Filesystem $filesystem Filesystem service object
	 * @param	ZipArchive $zip_archive PHP-native ZipArchive object
	 * @param	Verifier $verifier File verifier object
	 * @param	Logger $logger 	Updater logger object
	 * @param	RequirementsCheckerLoader $requirements Requirements checker loader object
	 */
	public function __construct(Filesystem $filesystem, ZipArchive $zip_archive, Verifier $verifier, Logger $logger, RequirementsCheckerLoader $requirements)
	{
		$this->filesystem = $filesystem;
		$this->zip_archive = $zip_archive;
		$this->verifier = $verifier;
		$this->logger = $logger;
		$this->requirements = $requirements;
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
	}
}

// EOF
