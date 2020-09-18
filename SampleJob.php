<?php

namespace Jobs;

use ExpressionEngine\Model\Channel\ChannelEntry;
use Queue\Job;
use Queue\Traits\Queueable;

class SampleJob extends Job {

	use Queueable;

	public $entry;

	public function __construct(ChannelEntry $entry)
	{
		$this->entry = $entry;

		parent::construct();
	}

	public function handle()
	{



	}

}