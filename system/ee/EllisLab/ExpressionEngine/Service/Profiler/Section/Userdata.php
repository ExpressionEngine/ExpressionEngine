<?php

namespace EllisLab\ExpressionEngine\Service\Profiler\Section;

use EllisLab\ExpressionEngine\Service\Profiler\ProfilerSection;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Userdata Profiler Section
 *
 * @package		ExpressionEngine
 * @subpackage	Profiler\Section
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Userdata extends ProfilerSection {

	/**
	 * @var userdata bits that we don't want to display
	 */
	private $skip = array('password', 'salt', 'unique_id', 'session_id', 'fingerprint');

	/**
	 * Set the section's data
	 *
	 * @return void
	 **/
	public function setData()
	{
		foreach (ee()->session->all_userdata() as $key => $value)
		{
			if (in_array($key, $this->skip))
			{
				continue;
			}

			$data[$key] = htmlspecialchars(print_r($value, TRUE));
		}

		$this->data = array('profiler_userdata' => $data);
	}
}
