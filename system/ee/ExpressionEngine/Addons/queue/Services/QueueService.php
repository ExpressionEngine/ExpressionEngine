<?php

namespace Queue\Services;

use Queue\Exceptions\QueueException;
use Queue\Models\Job;
use Queue\Services\SerializerService;

class QueueService {

	protected static $standardJobClassVariables = [
		'jobId',
		'attempts',
		'attemptsTaken',
		'sleep',
		'className',
		'runAt',
		'uuid',
	];

	public static function fire(Job $job)
	{
		$payload = $job->payload();

		$serializer = new SerializerService;

		$jobClass = $serializer->deserialize($payload);

		$jobClass->handle();

	}

}