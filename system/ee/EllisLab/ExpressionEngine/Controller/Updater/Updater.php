<?php

namespace EllisLab\ExpressionEngine\Controller\Updater;

use CP_Controller;
use EllisLab\ExpressionEngine\Service;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 4.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Updater Controller Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Updater extends CP_Controller {

	/**
	 * Request end-point for updater tasks
	 */
	public function index()
	{
		ee()->lang->loadfile('updater');
		ee()->load->library('el_pings');
		$version_file = ee()->el_pings->get_version_info();
		$to_version = $version_file[0][0];

		if (version_compare(APP_VER, $to_version, '>=') OR
			ee()->session->userdata('group_id') != 1)
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
			// TODO: Would be cool if UpdaterException returned formatted message
			// for web vs CLI
			$preflight_error = $e->getMessage();
		}

		ee()->load->helper('text');

		$vars = [
			'cp_page_title'   => lang('updating'),
			'site_name'       => ee()->config->item('site_name'),
			'current_version' => formatted_version(APP_VER),
			'to_version'      => formatted_version($to_version),
			'warn_message'    => $preflight_error,
			'next_step'       => $runner->getNextStep()
		];

		ee()->javascript->set_global('lang.fatal_error_caught', lang('fatal_error_caught'));

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

		$runner = ee('Updater/Runner');
		$runner->runStep($step);

		// If there is no next step, 'updateFiles' should be next in the micro app
		$next_step = $runner->getNextStep() ?: 'updateFiles';

		ee()->lang->loadfile('updater');

		return [
			'messageType' => 'success',
			'message' => lang($next_step.'_step'),
			'nextStep' => $next_step
		];
	}
}
// EOF
