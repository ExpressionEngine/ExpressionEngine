<?php

namespace EllisLab\ExpressionEngine\Controller\Updater;

use EllisLab\ExpressionEngine\Service\Updater\Runner;

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
			return [
				'messageType' => 'error',
				'message' => $e->getMessage()
			];
		}

		// Language and markup for front-end; each string
		// is what is sent back AFTER the corresponding key
		// step name has run
		$messages = [
			'updateFiles' => 'Files updated!'
		];

		return json_encode([
			'messageType' => 'success',
			'message' => $messages[$step],
			'nextStep' => $runner->getNextStep()
		]);
	}
}
// EOF
