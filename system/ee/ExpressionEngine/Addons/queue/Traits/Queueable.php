<?php

namespace ExpressionEngine\Addons\Queue\Traits;

use ExpressionEngine\Addons\Queue\Services\SerializerService;

trait Queueable {

	protected $jobId;
	protected $attemptsTaken = 0;
	protected $className;
	protected $runAt;
	protected $uuid;

	public function construct()
	{
		ee()->load->library('localize');
		ee()->load->helper('string');

		$this->uuid = uuid4();
		$this->className = get_class($this);
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

		$this->attemptsTaken++;

		if($this->attemptsTaken < $this->attempts()) {
			return $this->handle();
		}

		$job = ee('Model')->get('queue:Job')
					->filter('job_id', $this->jobId)
					->first();

		if($job) $job->delete();

		$failedJob = ee('Model')->make('queue:FailedJob');
		$failedJob->payload = $this->serialize();
		$failedJob->error = json_encode($exception);
		$failedJob->failed_at = ee()->localize->now;
		$failedJob->save();
	}

	protected function serialize()
	{
		$serializer = new SerializerService;
		return $serializer->serialize($this);
	}

	protected function attempts()
	{
		return $this->attempts ?: 1;
	}

	protected function sleep()
	{
		return $this->sleep ?: 5;
	}

	protected function runAt()
	{
		return $this->runAt ?: ee()->localize->now;
	}

}