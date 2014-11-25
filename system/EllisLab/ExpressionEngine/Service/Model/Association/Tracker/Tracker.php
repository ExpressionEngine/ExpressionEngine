<?php
namespace EllisLab\ExpressionEngine\Service\Model\Association\Tracker;

use EllisLab\ExpressionEngine\Service\Model\Model;

// Association change tracker
interface Tracker {

	public function getAdded();
	public function getRemoved();

	public function add(Model $model);
	public function remove(Model $model);

}