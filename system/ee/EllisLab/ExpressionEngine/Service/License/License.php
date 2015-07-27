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

	public function __construct($path_to_license, $pubkey)
	{
		if ( ! is_readable($path_to_license))
		{
			throw new Exception("Cannot read your license file: {$path_to_license}");
		}

		$license = file_get_contents($path_to_license);
		$license = unserialize(base64_decode($license));

		if ( ! isset($license['data']))
		{
			throw new Exception("The license is missing its data.");
		}

		$this->signed_data = $license['data'];
		$this->data = unserialize($license['data']);

		if (isset($license['signature']))
		{
			$this->signature = $license['signature'];
		}

		if (is_readable($pubkey))
		{
			$this->pubkey = file_get_contents($pubkey);
		}
		elseif (is_string($pubkey))
		{
			$this->pubkey = $pubkey;
		}
	}

	public function __get($key)
	{
		if (array_key_exists($key, $this->data))
		{
			return $this->data[$key];
		}

		throw new InvalidArgumentException("No such property: '{$key}' on ".get_called_class());
	}

	public function isValid()
	{
		if ($this->isSigned())
		{
			return $this->signatureIsValid();
		}

		return TRUE;
	}

	public function isSigned()
	{
		return ($this->signature !== NULL);
	}

	public function signatureIsValid()
	{
		if ( ! $this->isSigned())
		{
			return FALSE;
		}

		$r = openssl_verify($this->signed_data, $this->signature, $this->pubkey);

		// @TODO: Handle the -1 error response

		return ($r == 1) ? TRUE : FALSE;
	}
}