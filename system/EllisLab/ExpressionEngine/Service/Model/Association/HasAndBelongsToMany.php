<?php

namespace EllisLab\ExpressionEngine\Service\Model\Association;

class HasAndBelongsToMany extends ToMany {

	/**
	 *
	 */
	public function canSaveAcross()
	{
		return FALSE;
	}

	/**
	 *
	 */
	public function isStrongAssociation()
	{
		return FALSE;
	}

	/**
	 *
	 */
	protected function insertRelationship($target)
	{
		$this->relation->insertAssociation($this->source, $target);
	}

	/**
	 *
	 */
	protected function dropRelationship($target)
	{
		$this->relation->dropAssociation($this->source, $target);
	}
}