<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Consent;

/**
 * Consent CookieRegistry Service
 */
class CookieRegistry {

	/**
	 * @var int Value to indicate Necessary cookies
	 */
	const NECESSARY = 0;

	/**
	 * @var int Value to indicate Functionality cookies
	 */
	const FUNCTIONALITY = 1;

	/**
	 * @var int Value to indicate Performance cookies
	 */
	const PERFORMANCE = 2;

	/**
	 * @var int Value to indicate Targeting cookies
	 */
	const TARGETING = 4;

	/**
	 * @var array Registered cookies
	 */
	private $cookies = [];

	/**
	 * Register a cookie as Necessary
	 *
	 * @param  string $name Name of the cookie
	 * @return void
	 */
	public function registerNecessary($name)
	{
		$this->cookies[$name] = self::NECESSARY;
	}

	/**
	 * Register a cookie as Functionality
	 *
	 * @param  string $name Name of the cookie
	 * @return void
	 */
	public function registerFunctionality($name)
	{
		$this->cookies[$name] = self::FUNCTIONALITY;
	}

	/**
	 * Register a cookie as Performance
	 *
	 * @param  string $name Name of the cookie
	 * @return void
	 */
	public function registerPerformance($name)
	{
		$this->cookies[$name] = self::PERFORMANCE;
	}

	/**
	 * Register a cookie as Targeting
	 *
	 * @param  string $name Name of the cookie
	 * @return void
	 */
	public function registerTargeting($name)
	{
		$this->cookies[$name] = self::TARGETING;
	}

	/**
	 * Is this cookie Necessary?
	 *
	 * @param  string $name Name of the cookie
	 * @return boolean Whether or not the cookie is Necessary
	 */
	public function isNecessary($name)
	{
		return $this->cookieIs($name, self::NECESSARY);
	}

	/**
	 * Is this cookie Functionality?
	 *
	 * @param  string $name Name of the cookie
	 * @return boolean Whether or not the cookie is Functionality
	 */
	public function isFunctionality($name)
	{
		return $this->cookieIs($name, self::FUNCTIONALITY);
	}

	/**
	 * Is this cookie Performance?
	 *
	 * @param  string $name Name of the cookie
	 * @return boolean Whether or not the cookie is Performance
	 */
	public function isPerformance($name)
	{
		return $this->cookieIs($name, self::PERFORMANCE);
	}

	/**
	 * Is this cookie Targeting?
	 *
	 * @param  string $name Name of the cookie
	 * @return boolean Whether or not the cookie is Targeting
	 */
	public function isTargeting($name)
	{
		return $this->cookieIs($name, self::TARGETING);
	}

	/**
	 * Is the cookie registered?
	 *
	 * @param  string  $name Name of the cookie
	 * @return boolean Whether or not the cookie is in the registry
	 */
	public function isRegistered($name)
	{
		return (isset($this->cookies[$name]));
	}

	/**
	 * Get Cookie Type
	 * @param  string $name Name of the cookie
	 * @return int|boolean Type int of registered cookie, FALSE if cookie is not registered
	 */
	public function getType($name)
	{
		if ( ! isset($this->cookies[$name]))
		{
			return FALSE;
		}

		return $this->cookies[$name];
	}

	/**
	 * Check if the cookie is a certain type
	 *
	 * @param  string $name Name of the cookie
	 * @param  int $type Cookie type
	 * @return boolean Whether the cookie is the indicated type
	 */
	protected function cookieIsType($name, $type)
	{
		return (isset($this->cookies[$name]) && $this->cookies[$name] === $type);
	}
}
// END CLASS

// EOF
