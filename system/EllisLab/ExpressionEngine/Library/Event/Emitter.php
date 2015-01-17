<?php

namespace EllisLab\ExpressionEngine\Library\Event;

/**
 * Based loosely on the node.js class of the same name.
 */
class Emitter {

	protected $events = array();

	public function on($event, $listener)
	{
		$hash = $this->hash($listener);
		$this->events[$event][$hash] = $listener;

		return $this;
	}

	public function once($event, $listener)
	{
		$self = $this;

		$this->on($event, $listener);
		$this->on($event, function() use ($self, $event, $listener)
		{
			$self->off($event, $listener);
		});
	}

	public function off($event, $listener = NULL)
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

	protected function hash($listener)
	{
		return spl_object_hash($listener);
	}
}