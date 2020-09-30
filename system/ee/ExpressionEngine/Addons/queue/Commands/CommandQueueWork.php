<?php

namespace Queue\Commands;

use EllisLab\ExpressionEngine\Cli\Cli;

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
		$jobs = ee('Model')->get('queue:Job');

		
	}

}