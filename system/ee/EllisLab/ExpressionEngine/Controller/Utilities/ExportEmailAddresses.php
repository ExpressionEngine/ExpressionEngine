<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Controller\Utilities;

/**
 * Export Email Addresses Controller
 */
class ExportEmailAddresses extends Utilities {

	const CACHE_KEY = '/export/email';

	protected $batch_size = 10;
	protected $total_members;

	function __construct()
	{
		parent::__construct();

		if ( ! ee('Permission')->has('can_access_members'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$this->total_members = ee('Model')->get('Member')->count();
	}

	public function index()
	{
		ee()->cp->add_js_script('file', 'cp/utilities/export-email');

		ee()->javascript->set_global([
			'export_email' => [
				'endpoint'              => ee('CP/URL')->make('utilities/export-email-addresses/export')->compile(),
				'total_members'         => $this->total_members,
				'base_url'              => ee('CP/URL')->make('utilities/export-email-addresses')->compile(),
				'ajax_fail_banner'      => ee('CP/Alert')->makeInline('export-fail')
					->asIssue()
					->withTitle(lang('export_email_addresses_fail'))
					->addToBody('%body%')
					->render()
			]
		]);

		$vars = [
			'hide_top_buttons' => TRUE,
			'cp_page_title' => lang('export_email_addresses'),
			'base_url' => ee('CP/URL')->make('utilities/export-email-addresses/export'),
			'sections' => [
				[
					ee('CP/Alert')->makeInline('security')
						->asWarning()
						->withTitle(lang('important'))
						->addToBody(lang('export_warning_desc'))
						->addToBody(lang('will_be_logged'))
						->cannotClose()
						->render(),
					[
						'title'  => 'export_email_addresses_title',
						'desc'   => sprintf(lang('export_email_addresses_desc'), number_format($this->total_members)),
						'fields' => [
							'progress' => [
								'type'    => 'html',
								'content' => ee()->load->view('_shared/progress_bar', array('percent' => 0), TRUE)
							]
						]
					]
				]
			],
			'buttons' => [
				[
					'name' => 'export',
					'type' => 'submit',
					'value' => 'export',
					'text' => 'export',
					'working' => 'btn_exporting'
				]
			]
		];

		if (ee()->cache->get(self::CACHE_KEY))
		{
			$vars['buttons'][] = [
				'name' => 'download',
				'type' => 'submit',
				'value' => 'download',
				'text' => ucfirst(lang('download')),
				'working' => 'btn_downloading'
			];
		}

		ee()->cp->render('settings/form', $vars);
	}

	public function export()
	{
		// Only accept POST requests
		if ( ! ee('Request')->isPost())
		{
			show_404();
		}

		if (ee('Request')->post('download'))
		{
			$this->buildAndDownloadCSV();
		}

		$progress = (int) ee('Request')->post('progress');

		$members = ee('Model')->get('Member')
			->fields('member_id', 'username', 'screen_name', 'email')
			->offset($progress)
			->limit($this->batch_size)
			->all();

		$progress += $this->batch_size;

		$this->processBatch($members);

		if ($progress >= $this->total_members)
		{
			ee()->output->send_ajax_response(['status' => 'finished']);
		}

		ee()->output->send_ajax_response([
			'status' => 'in_progress',
			'progress' => $progress
		]);
	}

	protected function processBatch($members)
	{
		$export = ee()->cache->get(self::CACHE_KEY) ?: [];

		foreach ($members as $member)
		{
			$export[] = [
				'member_id'   => $member->member_id,
				'username'    => $member->username,
				'screen_name' => $member->screen_name,
				'email'       => $member->email
			];
		}

		ee()->cache->save(self::CACHE_KEY, $export);
	}

	protected function buildAndDownloadCSV()
	{
		$data = ee()->cache->get(self::CACHE_KEY) ?: [];
		ee()->cache->delete(self::CACHE_KEY);

		$csv = ee('CSV');

		foreach ($data as $datum)
		{
			$csv->addRow($datum);
		}

		ee()->logger->log_action(lang('exported_email_addresses'));

		ee()->load->helper('download');
		force_download('email-addresses.csv', (string) $csv);
	}

}
// END CLASS

// EOF
