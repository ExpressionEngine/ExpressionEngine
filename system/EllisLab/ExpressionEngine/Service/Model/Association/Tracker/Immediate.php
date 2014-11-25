<?php
namespace EllisLab\ExpressionEngine\Service\Model\Association\Tracker;

use EllisLab\ExpressionEngine\Service\Model\Model;

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