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
 * ExpressionEngine Get Profiler Section
 *
 * @package		ExpressionEngine
 * @subpackage	Profiler\Section
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Get extends ProfilerSection {

	/**
	 * Set the section's data
	 *
	 * @return void
	 **/
	public function setData()
	{
		if (count($_GET) == 0)
		{
			$data = lang('profiler_no_get');
		}
		else
		{
			foreach ($_GET as $key => $val)
			{
				if ( ! is_numeric($key))
				{
					$key = "'".$key."'";
				}

				$data["_GET[{$key}]"] = htmlspecialchars(stripslashes(print_r($val, TRUE)));
			}

		}

		$this->data = array(lang('profiler_get') => $data);
	}
}
