<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Service\Event;

/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

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