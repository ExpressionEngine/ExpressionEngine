<?php

namespace EllisLab\ExpressionEngine\Service\Model\Association\Tracker;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Immediate Association Tracker
 *
 * If an association uses this tracker, anything added to it is saved
 * immediately.
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Immediate implements Tracker {

	public function add(Model $model)
	{
		$model->save();
	}

	public function remove(Model $model)
	{
		$model->delete();
	}

	public function getAdded()
	{
		return array();
	}

	public function getRemoved()
	{
		return array();
	}

	public function reset()
	{
		// nada
	}

}