<?php

namespace EllisLab\ExpressionEngine\Service\Event;

use EllisLab\ExpressionEngine\Library\Mixin\Mixin as MixinInterface;

use Closure;

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
 * ExpressionEngine Event Mixin
 *
 * Allows any Mixable class to receive and emit events.
 *
 * @package		ExpressionEngine
 * @subpackage	Event
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
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