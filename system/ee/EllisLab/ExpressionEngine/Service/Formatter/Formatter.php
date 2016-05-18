<?php

namespace EllisLab\ExpressionEngine\Service\Formatter;

use EllisLab\ExpressionEngine\Core\Provider;

class Formatter {

	protected $provider;
	protected $lang;

	/**
	 *
	 */
	public function __construct(Provider $provider, $lang)
	{
		$this->provider = $provider;
		$this->lang = $lang;
		$this->lang->load('formatter');
	}
}

// EOF
