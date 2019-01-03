<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Utilities;

/**
 * Export Email Addresses Controller
 */
class ExportEmailAddresses extends Utilities {

	const CACHE_TTL = 300; // 5 mins
	const PREFIX = 'exprt';

	protected $batch_size = 10;
	protected $validated_batch_size = 5;
	protected $total_members;
	protected $domains = [];
	protected $export_path;

	function __construct()
	{
		parent::__construct();

		if ( ! ee('Permission')->has('can_access_members'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$this->total_members = ee('Model')->get('Member')->count();

		$this->export_path = ee('Request')->get('export_path', '');

		if ( ! empty($this->export_path))
		{
			$this->export_path = ee('Encrypt')->decode(
				$this->export_path,
				ee()->config->item('session_crypt_key')
			);
		}

		if (empty($this->export_path))
		{
			$this->export_path = self::PREFIX . '_' . ee('Encrypt')->generateKey();
		}
	}

	public function index()
	{
		$export_path = ee('Encrypt')->encode(
				$this->export_path,
				ee()->config->item('session_crypt_key')
			);

		ee()->cp->add_js_script('file', 'cp/utilities/export-email');

		ee()->javascript->set_global([
			'export_email' => [
				'endpoint'              => ee('CP/URL')->make('utilities/export-email-addresses/export', ['export_path' => $export_path])->compile(),
				'total_members'         => $this->total_members,
				'base_url'              => ee('CP/URL')->make('utilities/export-email-addresses', ['export_path' => $export_path])->compile(),
				'ajax_fail_banner'      => ee('CP/Alert')->makeInline('export-fail')
					->asIssue()
					->withTitle(lang('export_email_addresses_fail'))
					->addToBody('%body%')
					->render()
			]
		]);

		$vars = [
			'hide_top_buttons' => TRUE,
			'cp_page_title' => lang('mass_notification_export'),
			'base_url' => ee('CP/URL')->make('utilities/export-email-addresses/export', ['export_path' => $export_path]),
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

		$valid_cache = $this->getFromCache('valid');
		if ( ! empty($valid_cache))
		{
			$vars['buttons'][] = [
				'name' => 'download',
				'type' => 'submit',
				'value' => 'valid',
				'text' => 'download_valid_email_addresses',
				'working' => 'btn_downloading'
			];
		}

		$invalid_cache = $this->getFromCache('invalid');
		if ( ! empty($invalid_cache))
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
			$this->batch_size = $this->validated_batch_size;
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
			'progress' => $progress,
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
		$fs = ee('Filesystem');
		$path = $this->getPath($item);

		if ($fs->exists($path) && $fs->isFile($path))
		{
			return unserialize($fs->read($path));
		}

		return [];
	}

	protected function saveToCache($item, $data)
	{
		$fs = ee('Filesystem');
		$path = $this->getPath();

		if ( ! $fs->isDir($path))
		{
			$fs->mkdir($path);
		}

		$fs->touch($path);

		$fs->write($path . $item, serialize($data), TRUE);
	}

	protected function deleteCache($item)
	{
		$path = $this->getPath($item);

		try
		{
			$fs = ee('Filesystem');
			$fs->delete($path);
		}
		catch (\Exception $e)
		{
			return;
		}
	}

	public static function garbageCollect()
	{
		$fs = ee('Filesystem');

		foreach (glob(PATH_CACHE . self::PREFIX . '_*') as $path)
		{
			if ($fs->exists($path)
				&& $fs->isDir($path)
				&& ee()->localize->now > ($fs->mtime($path) + self::CACHE_TTL))
			{
				$fs->delete($path);
			}
		}
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

	protected function getPath($item = '')
	{
		$path = PATH_CACHE . $this->export_path . '/' . $item;
		return $path;
	}

}
// END CLASS

// EOF
