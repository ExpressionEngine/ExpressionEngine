<?php

namespace EllisLab\ExpressionEngine\Service\Updater\Downloader;

use EllisLab\ExpressionEngine\Service\Updater\Downloader\UpdaterPaths;
use EllisLab\ExpressionEngine\Service\Updater\UpdaterException;
use EllisLab\ExpressionEngine\Service\License\ExpressionEngineLicense;
use EllisLab\ExpressionEngine\Library\Filesystem\Filesystem;
use EllisLab\ExpressionEngine\Library\Curl\RequestFactory;
use EllisLab\ExpressionEngine\Service\Updater\Logger;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2017, EllisLab, Inc.
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
	use UpdaterPaths;

	protected $license;
	protected $curl;
	protected $filesystem;
	protected $logger;

	/**
	 * Constructor
	 *
	 * @param	ExpressionEngineLicense $license ExpressionEngineLicense object
	 * @param	Curl\RequestFactory $curl cURL service object
	 * @param	Filesystem $filesystem Filesystem service object
	 * @param	Logger $logger Updater logger object
	 */
	public function __construct(ExpressionEngineLicense $license, RequestFactory $curl, Filesystem $filesystem, Logger $logger)
	{
		$this->license = $license;
		$this->curl = $curl;
		$this->filesystem = $filesystem;
		$this->logger = $logger;
	}

	/**
	 * Performs the actual download of the update package and verifies its integrity
	 */
	public function downloadPackage($payload_url)
	{
		$this->logger->log('Downloading update package');

		$curl = $this->curl->post(
			$payload_url,
			[
				'action'  => 'download_update',
				'license' => $this->license->getRawLicense(),
				'version' => ee()->config->item('app_version'),
				'domain'  => ee()->config->item('site_url')
			]
		);

		$data = $curl->exec();

		$this->validateResponse($curl);

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

	private function validateResponse($curl)
	{
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
			throw new UpdaterException(lang('missing_signature_header')."\n\n".lang('try_again_later'), 6);
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
}

// EOF
