<?php
namespace EllisLab\ExpressionEngine\Service\License;

use InvalidArgumentException;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine LicenseFactory Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class LicenseFactory {

	/**
	 * @var string $default_public_key A default public key to use for verifying
	 *   signatures.
	 */
	protected $default_public_key;

	/**
	 * Constructor: sets a default public key
	 *
	 * @param string $pubkey A public key to use for verifying signatures
	 */
	public function __construct($pubkey)
	{
		$this->default_public_key = $pubkey;
	}

	/**
	 * Gets a license from the file system and return a License object.
	 *
	 * @param string $path_to_license The filesystem path to the license file
	 * @param string $pubkey A public key to use for verifying signatures (optional)
	 *
	 * @return License An object representing the license
	 */
	public function get($path_to_license, $pubkey = '')
	{
		$key = ($pubkey) ?: $this->default_public_key;
		return new License($path, $key);
	}
	/**
	 * Gets the ExpressionEngine license from the file system and returns
	 * an ExpressionEngineLicense object.
	 *
	 * @return ExpressionEngineLicense An object representing the license
	 */
	public function getEELicense($path = '')
	{
		// @TODO: Inject the path.
		$path = ($path) ?: SYSPATH.'user/config/license.key';
		return new ExpressionEngineLicense($path, $this->default_public_key);
	}

}