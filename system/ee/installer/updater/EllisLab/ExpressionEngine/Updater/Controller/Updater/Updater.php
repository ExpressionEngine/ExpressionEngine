<?php

namespace EllisLab\ExpressionEngine\Updater\Controller\Updater;

use EllisLab\ExpressionEngine\Updater\Service\Updater\Runner;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
class Updater {

	/**
	 * Request end-point for updater tasks
	 */
	public function index($step = '')
	{
		$runner = new Runner();

		$step = isset($_GET['step']) ? $_GET['step'] : FALSE;

		if ($step === FALSE OR $step == 'undefined')
		{
			$step = $runner->getFirstStep();
		}

		try
		{
			$runner->runStep($step);
		}
		catch (\Exception $e)
		{
			return json_encode([
				'messageType' => 'error',
				'message' => $e->getMessage()
			]);
		}

		// Language and markup for front-end; each string
		// is what is sent back AFTER the corresponding key
		// step name has run
		$messages = [
			'backupDatabase' => 'Backing up database<span>...</span>',
			'updateFiles' => 'Files updated!',
			'updateDatabase' => 'Running database updates...',
			'rollback' => 'Rolling back install...',
			'restoreDatabase' => 'Restoring database...',
		];

		// TODO: Make better
		if (strpos($step, 'backupDatabase') === 0)
		{
			$message = $messages['backupDatabase'];
		}
		elseif (strpos($step, 'updateDatabase') === 0)
		{
			$message = $messages['updateDatabase'];
		}
		else
		{
			$message = $messages[$step];
		}

		return json_encode([
			'messageType' => 'success',
			'message' => $message,
			'nextStep' => $runner->getNextStep()
		]);
	}
}
// EOF
