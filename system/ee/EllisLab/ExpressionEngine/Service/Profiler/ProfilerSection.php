<?php

namespace EllisLab\ExpressionEngine\Service\Profiler;

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
 * ExpressionEngine Profiler Section Interface
 *
 * Represents a Profiler Section that can be added to the profiler output.
 * Will be loaded from a profiler section string of the section's name
 * (first character lower case).
 *
 * @package		ExpressionEngine
 * @subpackage	Profiler
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
abstract class ProfilerSection {

	protected $data = array();

	abstract public function setData();

	public function render()
	{
		$view = ee('View')->make('_shared/profiler_section');
		return $view->render(array('profiler_data' => $this->data));
	}
}