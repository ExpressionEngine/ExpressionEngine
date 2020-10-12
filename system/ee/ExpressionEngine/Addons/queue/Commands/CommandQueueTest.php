<?php

namespace Queue\Commands;

use EllisLab\ExpressionEngine\Cli\Cli;
use Exception;
use Queue\Exceptions\QueueException;
use Queue\Models\Job;
use Queue\Services\QueueService;
use RuntimeException;
use Throwable;

class CommandQueueTest extends Cli {

	/**
	 * name of command
	 * @var string
	 */
	public $name = 'Test Queue';

	/**
	 * signature of command
	 * @var string
	 */
	public $signature = 'queue:test';

	/**
	 * Public description of command
	 * @var string
	 */
	public $description = 'Run sample job in queue';

	/**
	 * Summary of command functionality
	 * @var [type]
	 */
	public $summary = 'This will create a sample job for you to test'
						. ' your queue runner';

	/**
	 * How to use command
	 * @var string
	 */
	public $usage = 'php eecli queue:test';

	/**
	 * options available for use in command
	 * @var array
	 */
	public $commandOptions = [
		'verbose,v'	=> 'Verbose output'
	];