<?php

namespace Queue\Traits;

use ExpressionEngine\Service\Model\Model;

trait Queueable {

	protected $attempts;
	protected $className;
	protected $runAt;
	protected $uuid;

	public function __construct()
	{
		ee()->load->library('localize');

		$this->uuid = uuid4();
		$this->className = get_class(self);
	}

	public function create()
	{
		$job = ee('Model')->make('queue:Job');
		$job->payload = $this->serialize();
		$job->attempts = $this->attempts();
		$job->save();
	}

	public function fail($exception)
	{
		$job = ee('Model')->make('queue:FailedJob');
		$job->payload = $this->serialize();
		$job->error = json_encode($exception);
		$job->failed_at = ee()->localize->now;
		$job->save();
	}

	protected function serialize()
	{
		$vars = get_object_vars($this);

		return json_encode($vars);
	}

	protected function attempts()
	{
		return $this->attempts ?: 1;
	}

	protected function runAt()
	{
		return $this->runAt ?: ee()->localize->now;
	}

}