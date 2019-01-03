<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Updater\Controller\Updater;

use EllisLab\ExpressionEngine\Updater\Service\Updater\Runner;

/**
 * Updater controller, funnels update commands to the updater runner
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
