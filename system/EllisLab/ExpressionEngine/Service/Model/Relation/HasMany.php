<?php
namespace EllisLab\ExpressionEngine\Service\Model\Relation;

use EllisLab\ExpressionEngine\Service\Model\Model;
use EllisLab\ExpressionEngine\Service\Model\Association;

class HasMany extends HasOneOrMany {

	/**
	 *
	 */
	public function createAssociation(Model $source)
	{
		return new Association\HasMany($source, $this->name);
	}
}