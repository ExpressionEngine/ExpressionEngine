<?php

namespace EllisLab\ExpressionEngine\Library\Parser\Conditional;

class ConditionalRunner {

	private $prefix = '';
	private $safety = FALSE;
	private $protect_javascript = TRUE;

	public function disableProtectJavascript()
	{
		$this->protect_javascript = FALSE;
	}

	public function safetyOn()
	{
		$this->safety = TRUE;
	}

	public function setPrefix($prefix)
	{
		$this->prefix = $prefix;
	}


	/**
	 * Prep conditionals
	 *
	 * @access	public
	 * @param	string $str		The template string containing conditionals
	 * @param	string $vars	The variables to look for in the conditionals
	 * @param	string $safety	If TRUE, make sure conditionals are fully
	 *							parseable by replacing unknown variables with
	 *							FALSE. This defaults to FALSE so that conditionals
	 *							are slowly filled and then turned into safely
	 *							executable ones with the safety on at the end.
	 * @param	string $prefix	Prefix for the variables in $vars.
	 * @return	string The new template to use instead of $str.
	 */
	public function processConditionals($str, $vars)
	{
		// Protect compressed javascript from being mangled or interpreted as invalid
		if ($this->protect_javascript !== FALSE)
		{
			$protected_javascript = array();
			$js_protect = unique_marker('tmpl_script');

			if (stristr($str, '<script') && preg_match_all('/<script.*?>.*?<\/script>/is', $str, $matches))
			{
				foreach ($matches[0] as $i => $match)
				{
					$protected_javascript[$js_protect.$i] = $match;
				}

				$str = str_replace(array_values($protected_javascript), array_keys($protected_javascript), $str);
			}
		}

		$lexer = new ConditionalLexer();

		// Get the token stream
		$tokens = $lexer->tokenize($str);

		$parser = new ConditionalParser($tokens);

		$parser->setVariables(
			$this->prefixVariables($vars)
		);

		if ($this->safety === TRUE)
		{
			$parser->safetyOn();
		}

		$output = $parser->parse();

		// Unprotect <script> tags
		if ($this->protect_javascript !== FALSE && count($protected_javascript) > 0)
		{
			$output = str_replace(array_keys($protected_javascript), array_values($protected_javascript), $output);
		}

		return $output;
	}

	private function prefixVariables(array $vars)
	{
		$prefixed_vars = array();

		foreach ($vars as $key => $var)
		{
			$prefixed_vars[$this->prefix.$key] = $var;
		}

		return $prefixed_vars;
	}

}