<?php

return [
	'author'      		=> 'Packet Tide',
	'author_url'  		=> 'http://packettide.com',
	'name'        		=> 'Queue',
	'description' 		=> 'Runs scheduled jobs',
	'version'     		=> '1.0',
	'namespace'   		=> 'ExpressionEngine\Addons\Queue',
	'settings_exist'	=> true,
	// Advanced settings
	'services'			=> [
		'QueueService' => function($addon) {
			// Add your dependency injection here
			// See more here: https://docs.expressionengine.com/latest/development/addon-setup-php-file.html#services
		},
		'SerializerService' => function($addon) {
			// Add your dependency injection here
			// See more here: https://docs.expressionengine.com/latest/development/addon-setup-php-file.html#services
		},
	],
	'models'			=> [
		'Job'		=> 'Models\Job',
		'FailedJob'	=> 'Models\FailedJob',
	],
	'commands'	=> [
		'queue:test' => ExpressionEngine\Addons\Queue\Commands\CommandQueueTest::class,
		'queue:work' => ExpressionEngine\Addons\Queue\Commands\CommandQueueWork::class,
		'queue:flush' => ExpressionEngine\Addons\Queue\Commands\CommandQueueFlush::class,
	],
	'jobs' => [
		'SampleJob' => ExpressionEngine\Addons\Queue\Jobs\SampleJob::class,
	],
];