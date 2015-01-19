<?php

namespace EllisLab\ExpressionEngine\Service\Event;

use Closure;

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
 * ExpressionEngine Event Emitter
 *
 * Very basic event system ala node's emitter.
 *
 * @package		ExpressionEngine
 * @subpackage	Event
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Emitter {

	/**
	 * @var bound event listeners
	 */
	protected $events = array();

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
		$this->on($event, function() use ($self, $event, $listener)
		{
			$self->off($event, $listener);
		});
	}

	/**
	 * Unbind an event listener or all listeners on a given event
	 *
	 * @param String $event Event name
	 * @param Closure $listener The event listener callback [optional]
	 * @return $this
	 */
	public function off($event, Closure $listener = NULL)
	{
		if (isset($listener))
		{
			$hash = $this->hash($listener);
			unset($this->events[$event][$hash]);
		}
		else
		{
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

		if (isset($this->events[$event]))
		{
			foreach ($this->events[$event] as $listener)
			{
				call_user_func_array($listener, $args);
			}
		}

		return $this;
	}

	/**
	 * Support method to create a listener hash
	 *
	 * Down the line we might be able to support all callable's.
	 *
	 * @param Closure $listener Listener element
	 * @return String hash of the listener
	 */
	protected function hash(Closure $listener)
	{
		return spl_object_hash($listener);
	}
}