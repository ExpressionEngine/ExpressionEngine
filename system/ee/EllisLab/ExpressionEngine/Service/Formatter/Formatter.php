<?php

namespace EllisLab\ExpressionEngine\Service\Formatter;

use EllisLab\ExpressionEngine\Core\Provider;

class Formatter {

	protected $provider;
	protected $lang;
	protected $content;

	/**
	 *
	 */
	public function __construct($content, Provider $provider, $lang)
	{
		$this->content = $content;
		$this->provider = $provider;
		$this->lang = $lang;
		$this->lang->load('formatter');
	}

	public function __toString()
	{
		return $this->compile();
	}

	// by default we'll assume a string, but Formatters can override this and
	// use their own class props to handle non-string variables.
	public function compile()
	{
		return (string) $this->content;
	}
}

// EOF
