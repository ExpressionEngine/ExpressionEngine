<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\License;

use Exception;
use InvalidArgumentException;

/**
 * License Service
 */
class License {

	/**
	 * @var array $data The decoded and json_decoded license file data
	 */
	protected $data = array();

	/**
	 * @var string $singed_data The raw data that was potentially signed
	 */
	protected $signed_data;

	/**
	 * @var string $signature The cryptographic signature of the license file data
	 */
	protected $signature;

	/**
	 * @var string $pubkey The public key to use for verifying the signature
	 */
	protected $pubkey;

	/**
	 * @var string $path_to_license The filesystem path to the license file
	 */
	protected $path_to_license;

	/**
	 * @var array $errors An associative array of errors. The values contain a
	 *   default error message, however the keys should correspond to a
	 *   language key. The following keys are available:
	 *     corrupt_license_file
	 *     invalid_signature
	 *     missing_license
	 *     missing_pubkey
	 */
	protected $errors = array();

	/**
	 * @var bool @parsed A flag to determine if the license file has been parsed
	 */
	protected $parsed = FALSE;

	/**
	 * Constructor: sets the path to the license file and the public key. If the
	 * public key is empty it will record an error.
	 *
	 * @param string $path_to_license The filesystem path to the license file
	 * @param string $pubkey The public key to use for verifying the signature
	 */
	public function __construct($path_to_license, $pubkey)
	{
		$this->path_to_license = $path_to_license;
		$this->pubkey = $pubkey;

		if (empty($this->pubkey))
		{
			$this->errors['missing_pubkey'] = "EllisLab.pub is missing";
		}
	}

	/**
	 * Reads and returns the raw contents of the license file
	 *
	 * @return string Contents of license file
	 */
	public function getRawLicense()
	{
		if ( ! is_readable($this->path_to_license))
		{
			$this->errors['missing_license'] = "Cannot read your license file: {$this->path_to_license}";
			return;
		}

		return file_get_contents($this->path_to_license);
	}

	/**
	 * Attempts the load the license file from disk and parse it. It adds errors
	 * to $this->errors if it cannot read the license file or cannot find the
	 * data in the license file.
	 */
	protected function parseLicenseFile()
	{
		if ($this->parsed)
		{
			return;
		}

		$this->parsed = TRUE;

		// Reset the errors
		unset($this->errors['missing_license']);
		unset($this->errors['corrupt_license_file']);

		$license = json_decode(base64_decode($this->getRawLicense()), TRUE);

		if ( ! isset($license['data']))
		{
			$this->errors['corrupt_license_file'] = "The license is missing its data.";
			return;
		}

		$this->signed_data = $license['data'];
		$this->data = json_decode($license['data'], TRUE);

		if (isset($license['signature']))
		{
			$this->signature = base64_decode($license['signature']);
		}
	}

	/**
	 * Checks to see if any errors have been recorded.
	 *
	 * @return bool TRUE if there are errors, FALSE if not.
	 */
	public function hasErrors()
	{
		return ($this->errors != array());
	}

	/**
	 * Returns the error array
	 *
	 * @return array The errors array.
	 */
	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * Fetches a piece of data from the license file. It will first request that
	 * the license file be parsed. It will then check for the presence of the
	 * requested data and return it if present, or throw an error if not.
	 *
	 * @param string $key The piece of data being requested (i.e. 'license_number')
	 * @return mixed The value of the data as stored in the license file
	 */
	public function getData($key)
	{
		$this->parseLicenseFile();

		return isset($this->data[$key]) ? $this->data[$key] : NULL;
	}

	/**
	 * Fetches the signature from the license file and returns it.
	 *
	 * @return string The cryptographic signature from the license file.
	 */
	protected function getSignature()
	{
		$this->parseLicenseFile();
		return $this->signature;
	}

	/**
	 * Fetches the signed data from the license file and returns it.
	 *
	 * @return string The signed data from the license file.
	 */
	protected function getSignedData()
	{
		$this->parseLicenseFile();
		return $this->signed_data;
	}

	/**
	 * Requests that the license file be parsed, then runs the following checks:
	 *   - We found license data
	 *   - If the data was signed, the signure is valid
	 *
	 * @return bool TRUE if the license is valid, FALSE if not.
	 */
	public function isValid()
	{
		$this->parseLicenseFile();

		if (empty($this->data))
		{
			if ( ! isset($this->errors['missing_license']))
			{
				$this->errors['corrupt_license_file'] = "The license is missing its data.";
			}
			return FALSE;
		}

		$valid = $this->signatureIsValid();

		if ( ! $valid)
		{
			$this->errors['invalid_signature'] = "The license file has been tampered with";
		}

		return $valid;
	}

	/**
	 * Checks to see if a signature was provided in the license file
	 *
	 * @return bool TRUE if a signure was provided, FALSE if not.
	 */
	public function isSigned()
	{
		return ($this->getSignature() !== NULL);
	}

	/**
	 * Runs a cryptographic check to determine if the supplied signature is
	 * valid, provided a signature was provided (can't validate a missing
	 * signature).
	 *
	 * @return bool TRUE if the signature is valid, FALSE if not.
	 */
	public function signatureIsValid()
	{
		if ( ! $this->isSigned() || empty($this->pubkey))
		{
			return FALSE;
		}

		$r = openssl_verify($this->getSignedData(), $this->getSignature(), $this->pubkey);

		// @TODO: Handle the -1 error response

		return ($r == 1) ? TRUE : FALSE;
	}

}
