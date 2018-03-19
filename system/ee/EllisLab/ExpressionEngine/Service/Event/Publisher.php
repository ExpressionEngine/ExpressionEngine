<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Service\Event;

/**
 * Event Publisher Interface
 *
 * Interface to implement if your class publishes events.
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
