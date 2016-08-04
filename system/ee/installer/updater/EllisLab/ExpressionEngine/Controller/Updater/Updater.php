<?php

namespace EllisLab\ExpressionEngine\Controller\Updater;

use EllisLab\ExpressionEngine\Library\Filesystem\Filesystem;
use EllisLab\ExpressionEngine\Service;

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

	private $updater;

	/**
	 * Request end-point for updater tasks
	 */
	public function index($step = '')
	{
		$this->updater = $this->getUpdaterService();

		$step = isset($_GET['step']) ? $_GET['step'] : FALSE;

		if ($step === FALSE OR $step == 'undefined')
		{
			$step = 'updateFiles';
		}

		$step_groups = [
			'updateFiles' => [
				'updateFiles'
			]
		];

		foreach ($step_groups[$step] as $sub_step)
		{
			try
			{
				$this->updater->$sub_step();
			}
			catch (\Exception $e)
			{
				return json_encode([
					'messageType' => 'error',
					'message' => $e->getMessage()
				]);
			}
		}

		$messages = [
			'updateFiles' => 'Files updated!'
		];
		$next_step = [
			'updateFiles' => FALSE
		];

		return json_encode([
			'messageType' => 'success',
			'message' => $messages[$step],
			'nextStep' => $next_step[$step]
		]);
	}

	/**
	 * Constructs the updater service and assigns it to a class variable
	 */
	protected function getUpdaterService()
	{
		$filesystem = new Filesystem();
		$config = new Service\Config\File(SYSPATH.'user/config/config.php');
		$verifier = new Service\Updater\Verifier($filesystem);
		// TODO: prolly need to put this cache path into the configs.json and load that here
		$file_logger = new Service\Logger\File(SYSPATH.'user/cache/ee_update/update.log', $filesystem, php_sapi_name() === 'cli');
		$updater_logger = new Service\Updater\Logger($file_logger);

		return new Service\Updater\FileUpdater($filesystem, $config, $verifier, $updater_logger);
	}
}
// EOF
