<?php

namespace EllisLab\ExpressionEngine\Service\Formatter\Formats;

use EllisLab\ExpressionEngine\Service\Formatter\Formatter;

class Text extends Formatter {

	public function attribute_escape()
	{
		$this->content = htmlspecialchars($this->content, ENT_QUOTES, 'UTF-8');
		return $this;
	}

}

// EOF
