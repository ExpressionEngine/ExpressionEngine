<?php
namespace EllisLab\ExpressionEngine\Module\Channel\Core;

use EllisLab\ExpressionEngine\Core\Dependencies as Dependencies;
use EllisLab\ExpressionEngine\Module\Channel\Service\Validation\ValidationService;

class ChannelDependencies extends Dependencies {

	/**
	 *
	 */
	public function getValidationService()
	{
		if ( ! isset($this->validation_service))
		{
			$this->validation_service = new ChannelValidationService();
		}
	
		return $this->validation_service;
	}
	
}
