<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Event;

/**
 * Event Subscriber Interface
 *
 * Interface to implement if you want to subscribe your class to an event
 * emitter, where an event fired is automatically forwarded to on<EventName>
 * on your object.
 */
interface Subscriber
{
    /**
     * Get a list of subscribed event names
     *
     * @return array of event names (e.g. ['beforeSave', 'afterSave'])
     */
    public function getSubscribedEvents();
}
