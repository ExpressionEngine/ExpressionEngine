<?php

namespace EllisLab\ExpressionEngine\Service\Event;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Reflexive Event Interface
 *
 * Interface to implement if you want to support the mixin's reflexive
 * events, where an event fired is automatically forwarded to on<EventName>
 * on your object.
 *
 * @package		ExpressionEngine
 * @subpackage	Event
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
interface Reflexive {

	/**
	 * Get a list of reflexive event names
	 *
	 * @return Array of event names (e.g. ['beforeSave', 'afterSave'])
	 */
	public function getEvents();

}