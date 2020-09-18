<?php

namespace Queue;

abstract class Job {

	public function __construct()
	{

	}

	public function create()
	{

		$job = ee('Model')->make('queue:Job');

		$job->payload = $this->serialize();

		$job->save();

	}

	protected function serialize()
	{
		$vars = get_object_vars($this);

		return json_encode($vars);
	}

}