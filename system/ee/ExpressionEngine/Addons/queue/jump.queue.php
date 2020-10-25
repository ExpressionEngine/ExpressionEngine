<?php

use ExpressionEngine\Service\JumpMenu\AbstractJumpMenu;

class Queue_jump extends AbstractJumpMenu
{

	protected static $items = [
		'currentJobs' => [
			'icon'	=> 'fa-cogs',
			'command' => 'view current jobs',
			'command_title' => 'View Current Jobs',
			'dynamic' => false,
			'requires_keyword' => false,
			'target' => '',
		],
		'failedJobs' => [
			'icon'	=> 'fa-bomb',
			'command' => 'view failed jobs',
			'command_title' => 'View Failed Jobs',
			'dynamic' => false,
			'requires_keyword' => false,
			'target' => 'failed',
		],
		'flushAll' => [
			'icon'	=> 'fa-archive',
			'command' => 'flush queue',
			'command_title' => 'Flush Queue',
			'dynamic' => false,
			'requires_keyword' => false,
			'target' => 'flush_queue',
		],
		'flushFailed' => [
			'icon'	=> 'fa-archive',
			'command' => 'flush failed',
			'command_title' => 'Flush Failed Jobs',
			'dynamic' => false,
			'requires_keyword' => false,
			'target' => 'flush_failed',
		],
	];

}