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

	function __construct()
	{
		parent::__construct();

		if ( ! ee('Permission')->has('can_access_members'))
		{
			show_error(lang('unauthorized_access'), 403);
		}
	}

	public function index()
	{
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
						'title' => 'export_email_addresses_title',
						'desc' => sprintf(lang('export_email_addresses_desc'), number_format(ee('Model')->get('Member')->count())),
						'fields' => []
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

		ee()->cp->render('settings/form', $vars);
	}

	public function export()
	{
		// Only accept POST requests
		if (is_null(ee('Request')->post('export')))
		{
			show_404();
		}

		$csv = ee('CSV');

		$members = ee('Model')->get('Member')
			->fields('member_id', 'username', 'screen_name', 'email')
			->all();

		foreach ($members as $member)
		{
			$csv->addRow([
				'member_id'   => $member->member_id,
				'username'    => $member->username,
				'screen_name' => $member->screen_name,
				'email'       => $member->email
			]);
		}

		ee()->logger->log_action(lang('exported_email_addresses'));

		ee()->load->helper('download');
		force_download('email-addresses.csv', (string) $csv);
	}
}
// END CLASS

// EOF
