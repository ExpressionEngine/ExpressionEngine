<?php

namespace EllisLab\ExpressionEngine\Service\Event;

use EllisLab\ExpressionEngine\Library\Mixin\Mixin as MixinInterface;

class Mixin implements MixinInterface {

	protected $scope;
	protected $emitter;

	public function __construct($scope, $manager)
	{
		$this->scope = $scope;
		$this->bootReflexiveEvents();
	}

	/**
	 * Initialize the reflexive event listeners if the class supports it.
	 */
	protected function bootReflexiveEvents()
	{
		foreach ($this->scope->getEvents() as $event)
		{
			$this->on($event, function() use ($event) {
				$args = func_get_args();
				$model = array_shift($args);
				$event = 'on'.ucfirst($event);

				call_user_func_array(array($model, $event), $args);
			});
		}
	}

	/**
	 *
	 */
	public function on($event, $listener)
	{
		$this->getEventEmitter()->on($event, $listener);

		return $this->scope;
	}

	/**
	 * Emit an event
	 */
	public function emit(/* $event, ...$args */)
	{
		$args = func_get_args();
		array_splice($args, 1, 0, array($this->scope));

		return call_user_func_array(
			array($this->getEventEmitter(), 'emit'),
			$args
		);
	}

	/**
	 *
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
	 *
	 */
	public function setEventEmitter(Emitter $emitter)
	{
		$this->emitter = $emitter;
	}

	/**
	 *
	 */
	protected function newEventEmitter()
	{
		return new Emitter();
	}

}