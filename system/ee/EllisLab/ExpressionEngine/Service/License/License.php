<?php
namespace EllisLab\ExpressionEngine\Service\License;

use Exception;
use InvalidArgumentException;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine License Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class License {

	protected $data = array();
	protected $signed_data;
	protected $signature;
	protected $pubkey;
	protected $path_to_license;
	protected $errors = array();
	protected $parsed = FALSE;

	public function __construct($path_to_license, $pubkey)
	{
		$this->path_to_license = $path_to_license;
		$this->pubkey = $pubkey;

		if (empty($this->pubkey))
		{
			$this->errors['missing_pubkey'] = "EllisLab.pub is missing";
		}
	}

	protected function parseLicenseFile()
	{
		if ($parsed)
		{
			return;
		}

		// Reset the errors
		unset($this->errors['missing_license']);
		unset($this->errors['corrupt_license_file']);

		if ( ! is_readable($this->path_to_license))
		{
			$this->errors['missing_license'] = "Cannot read your license file: {$this->path_to_license}";
			return;
		}

		$license = file_get_contents($this->path_to_license);
		$license = unserialize(base64_decode($license));

		if ( ! isset($license['data']))
		{
			$this->errors['corrupt_license_file'] = "The license is missing its data.";
			return;
		}

		$this->signed_data = $license['data'];
		$this->data = unserialize($license['data']);

		if (isset($license['signature']))
		{
			$this->signature = $license['signature'];
		}

		$this->parsed = TRUE;
	}

	public function hasErrors()
	{
		return ($this->errors != array());
	}

	public function getErrors()
	{
		return $this->errors;
	}

	protected function getData($key)
	{
		$this->parseLicenseFile();

		if (array_key_exists($key, $this->data))
		{
			return $this->data[$key];
		}

		throw new InvalidArgumentException("No such property: '{$key}' on ".get_called_class());
	}

	protected function getSignature()
	{
		$this->parseLicenseFile();
		return $this->signature;
	}

	protected function getSignedData()
	{
		$this->parseLicenseFile();
		return $this->signed_data;
	}

	public function __get($key)
	{
		return $this->getData($key);
	}

	public function isValid()
	{
		$this->parseLicenseFile();

		if (empty($this->data))
		{
			return FALSE;
		}

		if ($this->isSigned())
		{
			$valid = $this->signatureIsValid();

			if ( ! $valid)
			{
				$errors['invalid_signature'] = "The license file has been tampered with";
			}

			return $valid;
		}

		return TRUE;
	}

	public function isSigned()
	{
		return ($this->getSignature() !== NULL);
	}

	public function signatureIsValid()
	{
		if ( ! $this->isSigned())
		{
			return FALSE;
		}

		$r = openssl_verify($this->getSignedData(), $this->getSignature(), $this->pubkey);

		// @TODO: Handle the -1 error response

		return ($r == 1) ? TRUE : FALSE;
	}
}