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
 * Event Subscriber Interface
 *
 * Interface to implement if you want to subscribe your class to an event
 * emitter, where an event fired is automatically forwarded to on<EventName>
 * on your object.
 */
interface Subscriber {

	/**
	 * Get a list of subscribed event names
	 *
	 * @return array of event names (e.g. ['beforeSave', 'afterSave'])
	 */
	public function getSubscribedEvents();

}
