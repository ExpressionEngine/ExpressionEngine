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
	public function run()
	{
		$step = isset($_GET['step']) ? $_GET['step'] : FALSE;

		if ($step === FALSE OR
			$step == 'undefined' OR
			strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST')
		{
			return 'The updater folder is still present. Delete the folder at system/ee/updater to access the control panel.';
		}

		$runner = new Runner();
		$runner->runStep($step);

		$next_step = $runner->getNextStep();

		return json_encode([
			'messageType' => 'success',
			'message' => $runner->getLanguageForStep($next_step),
			'nextStep' => $next_step
		]);
	}
}
// EOF
