<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\IpAddress;

/**
 * IP Address Service
 */
class Factory {

	/**
	 * Anonymize an IPv4 or IPv6 address.
	 *
	 * @param $address string IP address that must be anonymized
	 * @return string The anonymized IP address. Returns an empty string when the IP address is invalid.
	 */
	public function anonymize($address)
	{
		$anonymizer = new Anonymizer();
		return $anonymizer->anonymize($address);
	}
}
