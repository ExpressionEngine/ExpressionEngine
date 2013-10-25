<?php
namespace EllisLab\ExpressionEngine\Module\Channel\Core;

use EllisLab\ExpressionEngine\Core\Dependencies as Dependencies;
use EllisLab\ExpressionEngine\Module\Channel\Core\Validation\Validation;

class ChannelDependencies extends Dependencies {

	public function __construct(Dependencies $di)
	{
		parent::__construct($di);
	}

	/**
	 *
	 */
	public function getValidation()
	{
		if ( ! isset($this->validation))
		{
			$this->validation = new ChannelValidation();
		}
	
		return $this->validation;
	}
	
}
