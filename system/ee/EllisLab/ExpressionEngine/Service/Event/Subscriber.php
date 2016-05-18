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