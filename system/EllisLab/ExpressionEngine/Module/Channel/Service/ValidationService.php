<?php
namespace EllisLab\ExpressionEngine\Module\Channel\Service;

use EllisLab\ExpressionEngine\Service\Validation\ValidationService as ValidationService;

class ChannelValidationService extends ValidationService {

	public function getValidator()
	{
		return new ChannelValidator(self::$namespaces);
	}

}


