<?php

class Index {

	protected $index;
	protected $predicate;

	public function __construct(Closure $predicate)
	{
		$this->index = array();
		$this->predicate = $predicate;
	}

	public function fill($items)
	{
		foreach ($items as $item)
		{
			$this->add($item);
		}
	}

	public function add($item)
	{
		$predicate = $this->predicate;

		$this->index[$predicate($item)] = $item;
	}

	public function get($key)
	{
		return $this->has($key) ? $this->index[$key] : NULL;
	}

	public function has($key)
	{
		return isset($this->index[$key]);
	}
}

// EOF
