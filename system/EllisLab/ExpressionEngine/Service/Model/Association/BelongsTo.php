<?php

namespace EllisLab\ExpressionEngine\Service\Model\Association;

class BelongsTo extends ToOne {

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
}