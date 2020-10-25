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
		'take,t:'	=> 'Amount of jobs to run at a time',
		'once'		=> 'Run a single job in queue',
	];

	public function __construct()
	{
		parent::__construct();
		ee()->load->library('localize');
		ee()->load->helper('language');
	}

	public function handle()
	{
		$this->init();
		ee()->load->library('localize');

		$limit = $this->option('--once') ? 1 : $this->option('-t', 3);

		$jobs = ee('Model')
					->get('queue:Job')
					->filter('run_at', '<=', ee()->localize->format_date('%Y-%m-%d %H:%i:%s', ee()->localize->now, ee()->config->item('default_site_timezone')))
					->orFilter('run_at', null)
					->limit($limit)
					->all();

		foreach ($jobs as $job) {
			$this->output->outln('Processing ' . $job->job_id);
			$service = new QueueService($job);

			$result = $service->fire();

			if( ! $result['success'] ) {
				$this->handleJobException($result);
				$this->error('FAILED ' . $job->job_id);
				continue;
			}

			$this->info('Processed ' . get_class($result['jobClass']));
			sleep($result['jobClass']->sleep());
			$result['job']->delete();
		}
	}

	protected function handleJobException($result)
	{
		if($result['step'] == 'unserialize') {
			$this->handleJobExceptionAtObjectSerialization($result);
		}

		if($result['step'] == 'execution') {
			$this->handleJobExceptionAtJobExecution($result);
		}
	}

	protected function handleJobExceptionAtObjectSerialization($result)
	{
		$result['job']->delete();
		$failedJob = ee('Model')->make('queue:FailedJob');
		$failedJob->payload = $result['job']->payload();
		$failedJob->error = json_encode([
			'failed_at'	=> 'serialization',
			'message'	=> $result['message'],
		]);
		$failedJob->failed_at = ee()->localize->format_date('%Y-%m-%d %H:%i:%s', ee()->localize->now, ee()->config->item('default_site_timezone'));
		$failedJob->save();
	}

	protected function handleJobExceptionAtJobExecution($result)
	{
		$result['job']->fail();
	}

	protected function init()
	{
		defined('APP_NAME') || define('APP_NAME', 'ExpressionEngine');
		$databaseConfig = ee()->config->item('database');
		ee()->load->database();
		ee()->db->swap_pre = 'exp_';
		ee()->db->dbprefix = isset($databaseConfig['expressionengine']['dbprefix'])
								? $databaseConfig['expressionengine']['dbprefix']
								: 'exp_';
		ee()->db->db_debug = false;
	}

}