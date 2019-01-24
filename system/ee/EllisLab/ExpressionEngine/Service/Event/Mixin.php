<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Event;

use EllisLab\ExpressionEngine\Library\Mixin\Mixin as MixinInterface;

use Closure;

/**
 * Event Mixin
 *
 * Allows any Mixable class to receive and emit events.
 */
class Mixin implements MixinInterface, Publisher {

	/**
	 * @var The parent scope
	 */
	protected $scope;

	/**
	 * @var An event emitter instance
	 */
	protected $emitter;

	/**
	 * @param Object $scope The parent scope
	 */
	public function __construct($scope)
	{
		$this->scope = $scope;

		// Subscribe to events on self if the class is also a subscriber
		if ($scope instanceOf Subscriber)
		{
			$this->subscribe($scope);
		}
	}

	/**
	 * Get the mixin name
	 *
	 * @return String mixin name
	 */
	public function getName()
	{
		return 'Event';
	}

	/**
	 * Subscribe to events on this class
	 */
	public function subscribe(Subscriber $subscriber)
	{
		$this->getEventEmitter()->subscribe($subscriber);
	}

	/**
	 * Unsubscribe from events on this class
	 */
	public function unsubscribe(Subscriber $subscriber)
	{
		$this->getEventEmitter()->unsubscribe($subscriber);
	}

	/**
	 * Bind an event listener
	 *
	 * @param String $event Event name
	 * @param Closure $listener Event listener
	 * @return Scope object
	 */
	public function on($event, Closure $listener)
	{
		$this->getEventEmitter()->on($event, $listener);

		return $this->scope;
	}

	/**
	 * Emit an event
	 *
	 * @param String $event Event name
	 * @param Mixed ...rest Additional arguments to pass to the listener
	 * @return Scope object
	 */
	public function emit(/* $event, ...$args */)
	{
		call_user_func_array(
			array($this->getEventEmitter(), 'emit'),
			func_get_args()
		);

		return $this->scope;
	}

	/**
	 * Get the current event emitter instance
	 *
	 * @return Emitter object
	 */
	public function getEventEmitter()
	{
		if ( ! isset($this->emitter))
		{
			$this->setEventEmitter($this->newEventEmitter());
		}

		return $this->emitter;
	}

	/**
	 * Get the current event emitter instance
	 *
	 * @param Emitter $emitter Event emitter instance
	 * @return Scope object
	 */
	public function setEventEmitter(Emitter $emitter)
	{
		$this->emitter = $emitter;

		return $this->scope;
	}

	/**
	 * Create the default event emitter
	 *
	 * @return Emitter object
	 */
	protected function newEventEmitter()
	{
		return new Emitter();
	}

}
