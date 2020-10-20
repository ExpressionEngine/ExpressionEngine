<?php

namespace ExpressionEngine\Addons\Queue\Services;

use ExpressionEngine\Addons\Queue\Exceptions\QueueException;
use ExpressionEngine\Addons\Queue\Models\Job;
use ExpressionEngine\Addons\Queue\Services\SerializerService;

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

		$jobClass = $serializer->unserialize($payload);

		$jobClass->handle();

	}

}