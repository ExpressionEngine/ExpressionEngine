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

use Closure;

/**
 * Event Emitter
 *
 * Very basic event system ala node's emitter, with the addition
 * of a simple PubSub system.
 */
class Emitter implements Publisher
{
    /**
     * @var bound event listeners
     */
    protected $events = array();

    /**
     * @var Subscribers
     */
    protected $subscribers = array();

    /**
     * Bind an event listener
     *
     * @param String $event Event name
     * @param Closure $listener The event listener callback
     * @return $this
     */
    public function on($event, Closure $listener)
    {
        $hash = $this->hash($listener);
        $this->events[$event][$hash] = $listener;

        return $this;
    }

    /**
     * Bind an event listener that only fires once
     *
     * @param String $event Event name
     * @param Closure $listener The event listener callback
     * @return $this
     */
    public function once($event, Closure $listener)
    {
        $self = $this;

        $this->on($event, $listener);
        $this->on($event, function () use ($self, $event, $listener) {
            $self->off($event, $listener);
        });

        return $this;
    }

    /**
     * Subscribe an object to events on this emitter. Any public method
     * called `on<EventName>` will be considered a listener on that event.
     *
     * @param Subscriber $subscriber Subscriber to add
     */
    public function subscribe(Subscriber $subscriber)
    {
        $this->subscribers[$this->hash($subscriber)] = $subscriber;

        return $this;
    }

    /**
     * Remove a subscription. Less spam. Saves you money.
     *
     * @param Subscriber $subscriber Subscriber to remove
     */
    public function unsubscribe(Subscriber $subscriber)
    {
        unset($this->subscribers[$this->hash($subscriber)]);

        return $this;
    }

    /**
     * Unbind an event listener or all listeners on a given event
     *
     * @param String $event Event name
     * @param Closure $listener The event listener callback [optional]
     * @return $this
     */
    public function off($event, Closure $listener = null)
    {
        if (isset($listener)) {
            $hash = $this->hash($listener);
            unset($this->events[$event][$hash]);
        } else {
            unset($this->events[$event]);
        }

        return $this;
    }

    /**
     * Emit an event
     *
     * @param String $event Event name
     * @param Any number of additional parameters to pass to the listeners
     * @return $this
     */
    public function emit(/* $event, ...$args */)
    {
        $args = func_get_args();
        $event = array_shift($args);

        foreach ($this->subscribers as $subscriber) {
            if (in_array($event, $subscriber->getSubscribedEvents())) {
                $method = 'on' . ucfirst($event);
                call_user_func_array(array($subscriber, $method), $args);
            }
        }

        if (isset($this->events[$event])) {
            foreach ($this->events[$event] as $listener) {
                call_user_func_array($listener, $args);
            }
        }

        return $this;
    }

    /**
     * Support method to create a hash for listeners and subscribers
     *
     * Down the line we might want to support all callable's being
     * usable for listeners.
     *
     * @param Object $object Element to hash
     * @return String unique hash of the object
     */
    protected function hash($object)
    {
        return spl_object_hash($object);
    }
}

// EOF
