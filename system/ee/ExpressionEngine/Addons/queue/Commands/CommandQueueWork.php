<?php

namespace Queue\Commands;

use EllisLab\ExpressionEngine\Cli\Cli;
use Exception;
use Queue\Exceptions\QueueException;
use Queue\Models\Job;
use Queue\Services\QueueService;
use Throwable;

class CommandQueueWork extends Cli {

	/**
	 * name of command
	 * @var string
	 */
	public $name = 'Queue Worker';

	/**
	 * signature of command
	 * @var string
	 */
	public $signature = 'queue:work';

	/**
	 * Public description of command
	 * @var string
	 */
	public $description = 'Runs the EE queue';

	/**
	 * Summary of command functionality
	 * @var [type]
	 */
	public $summary = 'This will work the appropriate EE queue, and process'
						. ' any jobs that are in the queue.';

	/**
	 * How to use command
	 * @var string
	 */
	public $usage = 'php eecli queue:work';

	/**
	 * options available for use in command
	 * @var array
	 */
	public $commandOptions = [
		'verbose,v'				=> 'Verbose output',
	];

	public function handle()
	{

		ee()->load->library('localize');

		$jobs = ee('Model')->get('queue:Job')
					->filter('run_at', '<=', ee()->localize->now)
					->all();

		foreach ($jobs as $job) {
			try {
				QueueService::
			} catch (Exception $e) {
				$this->handleJobException($job, $exception);
			} catch (Throwable $e) {
				$this->handleJobException($job, $exception);
			} catch (QueueException $e) {
				$this->handleJobException($job, $exception);
			}
		}

	}

	protected function processJob(Job $job)
	{

	}

	protected function handleJobException(Job $job, $exception)
	{

	}

}