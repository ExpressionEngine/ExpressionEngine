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
 * Event Publisher Interface
 *
 * Interface to implement if your class publishes events.
 */
interface Publisher
{
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
