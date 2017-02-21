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
	public function run($step = '')
	{
		$step = isset($_GET['step']) ? $_GET['step'] : FALSE;

		if ($step === FALSE OR $step == 'undefined')
		{
			// TODO: Show a more helpful message here?
			return;
		}

		$runner = new Runner();
		$runner->runStep($step);

		$messages = [
			// This step isn't worth calling out, but needs to be a separate step
			// so that we can bootstrap EE, so don't update the front-end message
			'checkForDbUpdates' => '',
			'backupDatabase' => 'Backing up database',
			'updateDatabase' => 'Running updates',
			'rollback' => 'Rolling back install',
			'restoreDatabase' => 'Restoring database',
			'selfDestruct' => 'Cleaning up',
		];

		$next_step = $runner->getNextStep();
		$message = '';

		if ($next_step)
		{
			if (strpos($next_step, 'backupDatabase') === 0)
			{
				$message = $messages['backupDatabase'];
			}
			elseif (strpos($next_step, 'updateDatabase') === 0)
			{
				$message = $messages['updateDatabase'];
			}
			else
			{
				$message = $messages[$next_step];
			}
		}

		return json_encode([
			'messageType' => 'success',
			'message' => $message,
			'nextStep' => $runner->getNextStep()
		]);
	}
}
// EOF
