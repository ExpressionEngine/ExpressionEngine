<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use Queue\Job;

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

		$job = new Job

		$failedJob->delete();

		// Return something

	}

}