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
	const CACHE_TTL = 300; // 5 mins

	protected $batch_size = 10;
	protected $total_members;
	protected $domains = [];

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
					],
					[
						'title' => 'validate_addresses',
						'desc'  => 'validate_addresses_desc',
						'fields' => [
							'validate_email' => [
								'type' => 'toggle',
								'value' => 0
							]
						]
					]
				]
			],
			'buttons' => [
				[
					'name' => 'export',
					'type' => 'button',
					'value' => 'export',
					'text' => 'export',
					'working' => 'btn_exporting'
				]
			]
		];

		if ( ! empty($this->getFromCache('valid')))
		{
			$vars['buttons'][] = [
				'name' => 'download',
				'type' => 'submit',
				'value' => 'valid',
				'text' => 'download_valid_email_addresses',
				'working' => 'btn_downloading'
			];
		}

		if ( ! empty($this->getFromCache('invalid')))
		{
			$vars['buttons'][] = [
				'name' => 'download',
				'type' => 'submit',
				'value' => 'invalid',
				'text' => 'download_invalid_email_addresses',
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
			$this->buildAndDownloadCSV(ee('Request')->post('download'));
		}

		$progress = (int) ee('Request')->post('progress');

		if ($progress == 0)
		{
			$this->deleteCache('valid');
			$this->deleteCache('invalid');
		}

		if ($this->needsValidation())
		{
			$this->batch_size = 5;
		}

		$members = ee('Model')->get('Member')
			->fields('member_id', 'username', 'screen_name', 'email')
			->offset($progress)
			->limit($this->batch_size)
			->all();

		$progress += $this->batch_size;

		$this->processBatch($members);

		if ($progress >= $this->total_members)
		{
			$this->deleteCache('domains');
			ee()->output->send_ajax_response(['status' => 'finished']);
		}

		ee()->output->send_ajax_response([
			'status' => 'in_progress',
			'progress' => $progress
		]);
	}

	protected function processBatch($members)
	{
		$this->domains = $this->getFromCache('domains');
		$valid = $this->getFromCache('valid');
		$invalid = $this->getFromCache('invalid');

		foreach ($members as $member)
		{
			$data = [
				'member_id'   => $member->member_id,
				'username'    => $member->username,
				'screen_name' => $member->screen_name,
				'email'       => $member->email
			];

			if($this->emailIsValid($member->email))
			{
				$valid[] = $data;
			}
			else
			{
				$invalid[] = $data;
			}
		}

		$this->saveToCache('domains', $this->domains);
		$this->saveToCache('valid', $valid);
		$this->saveToCache('invalid', $invalid);
	}

	protected function buildAndDownloadCSV($type = 'valid')
	{
		$type = ($type == 'invalid') ? 'invalid' : 'valid';

		$data = $this->getFromCache($type);
		$this->deleteCache($type);

		$csv = ee('CSV');

		foreach ($data as $datum)
		{
			$csv->addRow($datum);
		}

		ee()->logger->log_action(lang('exported_' . $type . '_email_addresses'));

		ee()->load->helper('download');
		force_download($type . '-email-addresses.csv', (string) $csv);
	}

	protected function getFromCache($item)
	{
		return ee()->cache->get(self::CACHE_KEY . '/' . $item) ?: [];
	}

	protected function saveToCache($item, $data)
	{
		return ee()->cache->save(self::CACHE_KEY . '/' . $item, $data, self::CACHE_TTL);
	}

	protected function deleteCache($item)
	{
		return ee()->cache->delete(self::CACHE_KEY . '/' . $item);
	}

	protected function needsValidation()
	{
		$validate = ee('Request')->post('validate_email', FALSE);

		return ($validate == TRUE || $validate == 'y');
	}

	protected function emailIsValid($email)
	{
		if ( ! $this->needsValidation())
		{
			// Intentionally not validating, so all emails pass this check
			return TRUE;
		}

		if (filter_var($email, FILTER_VALIDATE_EMAIL) === FALSE)
		{
			return FALSE;
		}

		list($box, $domain) = explode('@', $email);

		if ( ! isset($this->domains[$domain]))
		{
			$this->domains[$domain] = checkdnsrr($domain);
		}

		return $this->domains[$domain];
	}

}
// END CLASS

// EOF
