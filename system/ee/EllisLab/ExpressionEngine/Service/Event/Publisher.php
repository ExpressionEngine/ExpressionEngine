<?php

namespace EllisLab\ExpressionEngine\Service\Event;

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
 * ExpressionEngine Event Publisher Interface
 *
 * Interface to implement if your class publishes events.
 *
 * @package		ExpressionEngine
 * @subpackage	Event
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
interface Publisher {

	/**
	 * Subscribe to this publisher
	 *
	 * @param Subscriber $subscriber New subscriber
	 */
	public function subscribe(Subscriber $subscriber);

	/**
	 * Unsubscribe from this publisher
	 *
	 * @param Subscriber $subscriber Current subscriber
	 */
	public function unsubscribe(Subscriber $subscriber);

}