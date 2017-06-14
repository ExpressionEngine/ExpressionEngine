<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
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
		// We only support the 'rollback' step from the query string
		$step = isset($_GET['step']) ? $_GET['step'] : FALSE;
		if ($step != 'rollback')
		{
			$step = isset($_SESSION['update_step']) ? $_SESSION['update_step'] : FALSE;
		}

		if ($step === FALSE OR strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST')
		{
			return 'The updater folder is still present. Delete the folder at system/ee/updater to access the control panel.';
		}

		$runner = new Runner();
		$runner->runStep($step);

		if (session_status() == PHP_SESSION_NONE) session_start();
		$_SESSION['update_step'] = $runner->getNextStep();
		$next_step = $_SESSION['update_step'];
		if ( ! $_SESSION['update_step'])
		{
			unset($_SESSION['update_step']);
		}
		session_write_close();

		return json_encode([
			'messageType' => 'success',
			'message' => $runner->getLanguageForStep($next_step),
			'hasRemainingSteps' => isset($_SESSION['update_step']),
			'updaterInPlace' => TRUE
		]);
	}
}
// EOF
