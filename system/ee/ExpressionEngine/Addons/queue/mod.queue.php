<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Queue {

	public function cancel()
	{

		$jobId = ee()->input->get_post('id');

		if( ! $jobId ) {
			return;
		}

		$job = ee('Model')->get('queue:Job')
					->filter('job_id', $jobId)
					->first();

		if( ! $job ) {
			return;
		}

		$job->delete();

		// Return something

	}

	public function retry()
	{

		ee()->load->library('localize');

		$failedJobId = ee()->input->get_post('id');

		if( ! $failedJobId ) {
			return;
		}

		$failedJob = ee('Model')->get('queue:FailedJob')
					->filter('job_id', $failedJobId)
					->first();

		if( ! $failedJob ) {
			return;
		}

		$job = ee('Model')->make(
			'queue:Job',
			[
				'payload' => $failedJob->payload,
				'attempts' => 0,
				'created_at' => ee()->localize->now,
			]
		);

		$failedJob->delete();

		// Return something

	}

}