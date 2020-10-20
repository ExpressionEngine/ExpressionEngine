<?php

namespace ExpressionEngine\Addons\Queue\Commands;

use ExpressionEngine\Cli\Cli;
use Exception;
use ExpressionEngine\Addons\Queue\Exceptions\QueueException;
use ExpressionEngine\Addons\Queue\Models\Job;
use ExpressionEngine\Addons\Queue\Services\QueueService;
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

		$this->init();

		ee()->load->library('localize');

		$jobs = ee('Model')->get('queue:Job')
					->filter('run_at', '<=', ee()->localize->now)
					->orFilter('run_at', null)
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

	protected function init()
	{
		$databaseConfig = ee()->config->item('database');
		ee()->load->database();
		ee()->db->swap_pre = 'exp_';
		ee()->db->dbprefix = isset($databaseConfig['expressionengine']['dbprefix'])
								? $databaseConfig['expressionengine']['dbprefix']
								: 'exp_';
		ee()->db->db_debug = false;
	}

}