<?php

namespace Queue\Commands;

require_once  __DIR__ . '/../SampleJob.php';

use EllisLab\ExpressionEngine\Cli\Cli;
use Queue\Jobs\SampleJob;

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

	public function handle()
	{

		$email = $this->ask('Choose an email, we\'ll send them an inspirational quote: ');

		ee()->load->helper('queue');

		queue(new SampleJob($email));

		$this->info('Job is queued! Run `php eecli queue:work` to process');

	}

}