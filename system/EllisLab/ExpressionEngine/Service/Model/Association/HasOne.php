<?php

namespace EllisLab\ExpressionEngine\Service\Model\Association;

class HasOne extends ToOne {

	/**
	 *
	 */
	public function canSaveAcross()
	{
		return TRUE;
	}

	/**
	 *
	 */
	public function isStrongAssociation()
	{
		return TRUE;
	}

}