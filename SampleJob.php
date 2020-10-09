<?php

namespace Jobs;

use ExpressionEngine\Model\Channel\ChannelEntry;
use Queue\Job;
use Queue\Traits\Queueable;

class SampleJob extends Job {

	use Queueable;

	public $entry;

	protected $attempts = 3;

	protected $sleep = 10;

	public function __construct(ChannelEntry $entry)
	{
		parent::construct();
		$this->entry = $entry;
	}

	public function handle()
	{



	}

}