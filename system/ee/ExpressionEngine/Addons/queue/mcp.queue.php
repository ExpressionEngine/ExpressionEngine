<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use ExpressionEngine\Library\CP\Table;

class Queue_mcp {

	/**
	 * Queue Homepage
	 *
	 * @access	public
	 * @return	string
	 */
	public function index()
	{

		if ( ! ee()->db->table_exists('queue_jobs'))
		{
			show_error(lang("queue_missing_table_queue_jobs"));
		}

		if ( ! ee()->db->table_exists('queue_failed_jobs'))
		{
			show_error(lang("queue_missing_table_queue_failed_jobs"));
		}

		$jobs = ee('Model')->get('queue:Job')->all();
		
		$this->generateSidebar();

		$vars = [
			'base_url' => ee('CP/URL')->make('addons/settings/queue/'),
			'cp_page_title' => lang('queue_module_name') . ' ' . lang('settings'),
			'save_btn_text' => 'btn_save_settings',
			'save_btn_text_working' => 'btn_saving',
			'jobs'	=> $jobs,
		];

		$jobsTable = $this->createJobsTable($jobs);

		$vars['jobs_table'] = $jobsTable->viewData(ee('CP/URL', 'queue_jobs'));

		$vars['tabs'] = [
			'jobs_table' => $jobsTable->viewData(ee('CP/URL', 'queue_jobs')),
		];

		return [
			'body' => ee('View')->make('queue:index')->render($vars),
			'breadcrumb' => [
				ee('CP/URL')->make('addons/settings/queue')->compile() => lang('queue_module_name')
			],
			'heading' => lang('queue_module_name')
		];
	}

	public function failed()
	{

		if ( ! ee()->db->table_exists('queue_jobs'))
		{
			show_error(lang("queue_missing_table_queue_jobs"));
		}

		if ( ! ee()->db->table_exists('queue_failed_jobs'))
		{
			show_error(lang("queue_missing_table_queue_failed_jobs"));
		}

		$failedJobs = ee('Model')->get('queue:FailedJob')->all();
		$this->generateSidebar();

		$vars = [
			'base_url' => ee('CP/URL')->make('addons/settings/queue/'),
			'cp_page_title' => lang('queue_module_name') . ' ' . lang('settings'),
			'save_btn_text' => 'btn_save_settings',
			'save_btn_text_working' => 'btn_saving',
			'failed_jobs'	=> $failedJobs,
		];

		$failedJobsTable = $this->createFailedJobsTable($failedJobs);
		$vars['failed_jobs_table'] = $failedJobsTable->viewData(ee('CP/URL', 'queue_failed_jobs'));

		return [
			'body' => ee('View')->make('queue:failed')->render($vars),
			'breadcrumb' => [
				ee('CP/URL')->make('addons/settings/queue')->compile() => lang('queue_module_name')
			],
			'heading' => lang('queue_module_name')
		];

		if ( ! ee()->db->table_exists('queue_jobs'))
		{
			show_error(lang("queue_missing_table_queue_jobs"));
		}

		if ( ! ee()->db->table_exists('queue_failed_jobs'))
		{
			show_error(lang("queue_missing_table_queue_failed_jobs"));
		}

		$jobs = ee('Model')->get('queue:Job')->all();
		
		$this->generateSidebar();

		$vars = [
			'base_url' => ee('CP/URL')->make('addons/settings/queue/'),
			'cp_page_title' => lang('queue_module_name') . ' ' . lang('settings'),
			'save_btn_text' => 'btn_save_settings',
			'save_btn_text_working' => 'btn_saving',
			'jobs'	=> $jobs,
			'failed_jobs'	=> $failedJobs,
		];
		

		$jobsTable = $this->createJobsTable($jobs);
		$failedJobsTable = $this->createFailedJobsTable($failedJobs);

		$vars['jobs_table'] = $jobsTable->viewData(ee('CP/URL', 'queue_jobs'));
		$vars['failed_jobs_table'] = $jobsTable->viewData(ee('CP/URL', 'queue_failed_jobs'));

		$vars['tabs'] = [
			'jobs_table'		=> $jobsTable->viewData(ee('CP/URL', 'queue_jobs')),
			'failed_jobs_table' => $jobsTable->viewData(ee('CP/URL', 'queue_failed_jobs')),
		];

		return ee('View')->make('queue:index')->render($vars);
	}

	private function createJobsTable($jobs)
	{

		$table = ee(
			'CP/Table',
			[
				'autosort' => true,
				'autosearch' => true,
			]
		);

		$table->setColumns(
			[
		    	'queue_jobs_id',
		    	'queue_payload',
				'queue_attempts',
				'queue_run_at',
				'queue_created_at',
				'manage' => [
					'type'  => Table::COL_TOOLBAR
				],
				[
					'type'  => Table::COL_CHECKBOX
				]
			]
		);

		$data = [];

		foreach ($jobs as $job) {

			$cancelUrl = ee('CP/URL', 'queue/cancel/' . $job->getId());

			$data[] = [
				$job->job_id,
				$job->payload,
				$job->attempts,
				$job->run_at,
				$job->created_at,
				[
					'toolbar_items' => [
						'queue_job_cancel' => [
							'href' => $cancelUrl,
							'title' => lang('queue_job_cancel'),
						]
					],
				],
				[
					'name' => 'jobs[]',
					'value' => $job->getId(),
					'data'  => [
						'confirm' => lang('queue_jobs_id') . ': <b>' . htmlentities($job->getId(), ENT_QUOTES) . '</b>'
					],
				],
			];
		}

		$table->setNoResultsText('queue_no_jobs');
		$table->setData($data);

		return $table;

	}

	private function createFailedJobsTable($jobs)
	{

		$table = ee(
			'CP/Table',
			[
				'autosort' => true,
				'autosearch' => true,
			]
		);

		$table->setColumns(
			[
		    	'queue_jobs_id',
		    	'queue_payload',
				'queue_failed_error',
				'queue_failed_failed_at',
				'manage' => [
					'type'  => Table::COL_TOOLBAR
				],
				[
					'type'  => Table::COL_CHECKBOX
				]
			]
		);

		$data = [];

		foreach ($jobs as $job) {

			$retryUrl = ee('CP/URL', 'queue/retry/' . $job->getId());

			$data[] = [
				$job->failed_job_id,
				$job->payload,
				$job->error,
				$job->failed_at,
				[
					'toolbar_items' => [
						'queue_retry' => [
							'href' => $retryUrl,
							'title' => lang('queue_job_cancel'),
						]
					],
				],
				[
					'name' => 'jobs[]',
					'value' => $job->getId(),
					'data'  => [
						'confirm' => lang('queue_jobs_id') . ': <b>' . htmlentities($job->getId(), ENT_QUOTES) . '</b>'
					],
				],
			];
		}

		$table->setNoResultsText('queue_no_failed_jobs');
		$table->setData($data);

		return $table;

	}

	protected function generateSidebar( $active = null )
	{
		$service = ee('CP/Sidebar')->make();

		$sidebar = $service->addHeader(lang('formgrab_forms'));

		$sidebarList = $sidebar->addBasicList();
		$sidebarList->addItem(lang('queue_jobs'), ee('CP/URL', 'addons/settings/queue'));
		$sidebarList->addItem(lang('queue_failed_jobs'), ee('CP/URL', 'addons/settings/queue/failed'));

		return $sidebar;
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
				'created_at' => ee()->localize->format_date('%r', ee()->localize->now),
			]
		);

		$failedJob->delete();

		// Return something

	}

}