<?php
namespace EllisLab\ExpressionEngine\Service\Model\Relation;

use EllisLab\ExpressionEngine\Service\Model\Model;
use EllisLab\ExpressionEngine\Service\Model\Association;

class HasOne extends HasOneOrMany {

	/**
	 *
	 */
	public function createAssociation(Model $source)
	{
		return new Association\HasOne($source, $this->name);
	}
}