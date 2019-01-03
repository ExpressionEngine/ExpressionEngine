<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Updater;

use CP_Controller;
use EllisLab\ExpressionEngine\Service;

/**
 * Updater controller, funnels update commands to the updater runner
 */
class Updater extends CP_Controller {

	/**
	 * Early permissions checks
	 */
	public function __construct()
	{
		parent::__construct();

		if (ee()->session->userdata('group_id') != 1 OR
			ee('Request')->method() != 'POST')
		{
			show_error(lang('unauthorized_access'), 403);
		}
	}

	/**
	 * Request end-point for updater tasks
	 */
	public function index()
	{
		ee()->lang->loadfile('updater');
		ee()->load->library('el_pings');
		$version_file = ee()->el_pings->get_version_info(TRUE);
		$current_version = ee()->config->item('app_version');
		$to_version = $version_file['latest_version'];

		$newer_version_available = version_compare($current_version, $to_version, '<');

		if ( ! $newer_version_available)
		{
			return ee()->functions->redirect(ee('CP/URL', 'homepage'));
		}

		$preflight_error = NULL;
		$runner = ee('Updater/Runner');
		try
		{
			// Run preflight first and go ahead and show those errors
			$runner->runStep($runner->getFirstStep());
		}
		catch (\Exception $e)
		{
			$preflight_error = str_replace("\n", '<br>', $e->getMessage());
		}

		ee()->load->helper('text');

		$next_step = $runner->getNextStep();
		$vars = [
			'cp_page_title'   => lang('updating'),
			'site_name'       => ee()->config->item('site_name'),
			'current_version' => formatted_version($current_version),
			'to_version'      => formatted_version($to_version),
			'warn_message'    => $preflight_error,
			'first_step'      => $runner->getLanguageForStep($next_step),
			'next_step'       => $next_step
		];

		ee()->javascript->set_global([
			'lang.fatal_error_caught' => lang('fatal_error_caught'),
			'lang.we_stopped_on' => lang('we_stopped_on')
		]);

		return ee('View')->make('updater/index')->render($vars);
	}

	/**
	 * AJAX endpoint for the updater
	 */
	public function run()
	{
		$step = ee()->input->get('step');

		if ($step === FALSE OR $step == 'undefined')
		{
			return;
		}

		// This step should not have hit this controller
		if ($step == 'updateFiles')
		{
			ee()->lang->loadfile('updater');
			throw new \Exception(lang('out_of_date_admin_php'));
		}

		$runner = ee('Updater/Runner');
		$runner->runStep($step);

		// If there is no next step and we're not rolling back, 'updateFiles'
		// should be next in the micro app
		$next_step = $runner->getNextStep();
		if ($next_step === FALSE && $step !== 'rollback')
		{
			$next_step = 'updateFiles';
		}

		return [
			'messageType' => 'success',
			'message' => $runner->getLanguageForStep($next_step),
			'nextStep' => $next_step
		];
	}
}
// EOF
