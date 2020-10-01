<?php

namespace Queue\Services;

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

	}

}