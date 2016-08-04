<?php

namespace EllisLab\ExpressionEngine\Controller\Updater;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;

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
		// TODO: Can we catch a PHP timeout and report that to the user?
		// TODO: Prolly just restrict super admins to auto-updating

		$step = ee()->input->get('step');

		if ($step === FALSE OR $step == 'undefined')
		{
			$step = 'preflight';
		}

		$step_groups = [
			'preflight' => [
				'preflight'
			],
			'download' => [
				'downloadPackage'
			],
			'unpack' => [
				'unzipPackage',
				'verifyExtractedPackage',
				'checkRequirements',
				'moveUpdater'
			]
		];

		foreach ($step_groups[$step] as $sub_step)
		{
			try
			{
				ee('Updater\Downloader')->$sub_step();
			}
			catch (\Exception $e)
			{
				return [
					'messageType' => 'error',
					'message' => $e->getMessage()
				];
			}
		}

		$messages = [
			'preflight' => 'Downloading update<span>...</span>',
			'download' => 'Unpacking update<span>...</span>',
			'unpack' => 'Updating files<span>...</span>'
		];
		$next_step = [
			'preflight' => 'download',
			'download' => 'unpack',
			'unpack' => 'updateFiles'
		];

		return [
			'messageType' => 'success',
			'message' => $messages[$step],
			'nextStep' => $next_step[$step]
		];
	}
}
// EOF
