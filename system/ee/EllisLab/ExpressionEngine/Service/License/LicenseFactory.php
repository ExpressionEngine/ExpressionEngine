<?php
namespace EllisLab\ExpressionEngine\Service\License;

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
 * ExpressionEngine LicenseFactory Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class LicenseFactory {

	protected $default_key;

	public function __construct($pubkey)
	{
		$this->default_key = $pubkey;
	}

	public function get($path_to_license, $pubkey = '')
	{
		$key = ($pubkey) ?: $this->default_key;
		return new License($path, $key);
	}

	public function getEELicense()
	{
		// @TODO: Inject the path.
		$path = SYSPATH.'user/config/license.key';
		try
		{
			$license = new ExpressionEngineLicense($path, $this->default_key);
		}
		catch (Exception $e)
		{
			$license = NULL;
		}

		return $license;
	}

}