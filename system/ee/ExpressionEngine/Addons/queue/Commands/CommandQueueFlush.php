<?php

namespace ExpressionEngine\Addons\Queue\Commands;

use ExpressionEngine\Cli\Cli;
use ExpressionEngine\Addons\Queue\Jobs\SampleJob;

class CommandQueueFlush extends Cli {

	/**
	 * name of command
	 * @var string
	 */
	public $name = 'Test Queue';

	/**
	 * signature of command
	 * @var string
	 */
	public $signature = 'queue:flush';

	/**
	 * Public description of command
	 * @var string
	 */
	public $description = 'Clears queue and failed jobs';

	/**
	 * Summary of command functionality
	 * @var [type]
	 */
	public $summary = 'This will get rid of all jobs.';

	/**
	 * How to use command
	 * @var string
	 */
	public $usage = 'php eecli queue:flush';

	/**
	 * options available for use in command
	 * @var array
	 */
	public $commandOptions = [
		'verbose,v'		=> 'Verbose output',
		'failed-only'	=> 'Flush failed jobs only',
		'fresh-only'	=> 'Flush current jobs only',
	];

	public function handle()
	{

		$this->init();

		if( ! $this->option('--fresh-only') ) {
			$this->info('Deleting all current jobs');

			$jobs = ee('Model')->get('queue:Job')->all();

			foreach ($jobs as $job) {
				$job->delete();
			}
		}

		if( ! $this->option('--failed-only') ) {
			$this->info('Deleting all failed jobs');

			$jobs = ee('Model')->get('queue:FailedJob')->all();

			foreach ($jobs as $job) {
				$job->delete();
			}
		}

		$this->info('Queue flushed');

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