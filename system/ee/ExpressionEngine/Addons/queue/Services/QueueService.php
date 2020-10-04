<?php

namespace Queue\Services;

use Queue\Exceptions\QueueException;
use Queue\Models\Job;

class QueueService {

	protected static $standardJobClassVariables = [
		'jobId',
		'attempts',
		'attemptsTaken',
		'className',
		'runAt',
		'uuid',
	];

	public static function fire(Job $job)
	{
		$payload = $job->payload();

		$class = $job->className;

		$classVars = [];

		foreach ($payload as $classVar) {
			if(in_array($classVar, self::$standardJobClassVariables)) {
				continue;
			}

			$classVars[] = 
		}

		$jobClass = new $class(...$classVars);

	}

}