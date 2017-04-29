<?php

namespace EllisLab\ExpressionEngine\Service\Event;

/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Event Subscriber Interface
 *
 * Interface to implement if you want to subscribe your class to an event
 * emitter, where an event fired is automatically forwarded to on<EventName>
 * on your object.
 *
 * @package		ExpressionEngine
 * @subpackage	Event
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
interface Subscriber {

	/**
	 * Get a list of subscribed event names
	 *
	 * @return Array of event names (e.g. ['beforeSave', 'afterSave'])
	 */
	public function getSubscribedEvents();

}