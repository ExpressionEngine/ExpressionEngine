<?php

namespace Queue\Commands;

use EllisLab\ExpressionEngine\Cli\Cli;
use Exception;
use Queue\Exceptions\QueueException;
use Queue\Models\Job;
use Queue\Services\QueueService;
use RuntimeException;
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
		'verbose,v'	=> 'Verbose output',
		'take,t:'	=> 'Amount of jobs to run at a time'
	];

	public function handle()
	{

		ee()->load->library('localize');

		$jobs = ee('Model')->get('queue:Job')
					->filter('run_at', '<=', ee()->localize->now)
					->limit($this->option('-t', 3))
					->all();

		foreach ($jobs as $job) {
			try {
				$this->output->outln('Processing ' . get_class($job));
				QueueService::fire($job);
				$this->info('Processed ' . get_class($job));
				sleep($job->sleep());
			} catch (Exception $e) {
				$this->handleJobException($job, $e);
			} catch(RuntimeException $e) {
				$this->handleJobException($job, $e);
			} catch (Throwable $e) {
				$this->handleJobException($job, $e);
			} catch (QueueException $e) {
				$this->handleJobException($job, $e);
			}
		}

	}

	protected function handleJobException(Job $job, $exception)
	{
		$this->error('FAILED ' . get_class($job));
		$job->fail($exception);
	}

}