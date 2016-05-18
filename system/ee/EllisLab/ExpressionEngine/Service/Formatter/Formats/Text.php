<?php

namespace EllisLab\ExpressionEngine\Service\Formatter\Formats;

use EllisLab\ExpressionEngine\Service\Formatter\Formatter;

class Text extends Formatter {

	public function attribute_escape($str)
	{
		return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
	}

}

// EOF
