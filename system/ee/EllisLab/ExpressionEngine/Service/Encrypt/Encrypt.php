<?php
namespace EllisLab\ExpressionEngine\Service\Encrypt;

use EllisLab\ExpressionEngine\Service\Encrypt\Driver;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.5.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Encrypt Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Encrypt {

	private $driver;

	public function __construct(Driver $driver)
	{
		$this->setDriver($driver);
	}

	public function setDriver(Driver $driver)
	{
		$this->driver = $driver;
	}

	public function getDriver()
	{
		return $this->driver;
	}

	public function encode($string, $key)
	{
		return $this->driver->encode($string, $key);
	}

	public function decode($data, $key)
	{
		return $this->driver->decode($data, $key);
	}

}

// EOF
