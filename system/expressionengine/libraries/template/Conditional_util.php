<?php

class Conditional_util {

	private $debug = FALSE;
	private $protect_javascript = TRUE;

	public function disable_protect_javascript()
	{
		$this->protect_javascript = FALSE;
	}

	public function enable_debug()
	{
		$this->debug = TRUE;
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
	public function prep_conditionals($str, $vars, $safety = FALSE, $prefix = '')
	{
		if (isset(ee()->TMPL->embed_vars))
		{
			// If this is being called from a module tag, embedded variables
			// aren't going to be available yet.  So this is a quick workaround
			// to ensure advanced conditionals using embedded variables can do
			// their thing in mod tags.
			$vars = array_merge($vars, ee()->TMPL->embed_vars);
		}

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

		// Prefix passed in variables
		$prefixed_vars = array();

		foreach ($vars as $key => $var)
		{
			$prefixed_vars[$prefix.$key] = $var;// $this->encode_conditional_value($var, $safety);
		}

		$vars = $prefixed_vars;

		require_once APPPATH.'libraries/template/Conditional_lexer.php';

		$lexer = new Conditional_lexer();

		// Get the token stream
		$tokens = $lexer->tokenize($str);

		require_once APPPATH.'libraries/template/Conditional_parser.php';

		$parser = new Conditional_parser($tokens);
		$parser->setVariables($vars);

		if ($safety === TRUE)
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

	/**
	 * Checks a conditional to ensure it isn't trying to do something unsafe:
	 * e.g looks for unquoted backticks (`) and PHP comments
	 *
	 * @access	public
	 * @param	string	$str	The conditional string for parsig
	 * @return	boolean	TRUE if the conditional is unsafe, FALSE otherwise
	 */
	public function conditional_is_unsafe($str)
	{
		$length   = strlen($str);
		$escaped  = FALSE;
		$str_open = '';

		for ($i = 0; $i < $length; $i ++)
		{
			// escaped in string is always valid
			if ($escaped)
			{
				$escaped = FALSE;
				continue;
			}

			$char = $str[$i];

			switch ($char)
			{
				case '`':
					if ( ! $str_open )
					{
						return TRUE;
					}
					break;
				case '\\':
					$escaped = TRUE;
					break;
				case '/':
					if (($str[$i + 1] == '/' || $str[$i + 1] == '*') && ! $str_open)
					{
						return TRUE;
					}
					break;
				case '#':
					if ( ! $str_open )
					{
						return TRUE;
					}
					break;
				case '"':
				case "'":
					$str_open = ($char == $str_open) ? '' : $char;
					break;
			}
		}

		return FALSE;
	}


	/**
	 * Encodes values for use in conditionals
	 *
	 * @access	public
	 * @param	string $value	The conditional value to encoded
	 * @param	string $safety	If TRUE, make sure conditionals are fully
	 *							parseable by replacing unknown variables with
	 *							FALSE. This defaults to FALSE so that conditionals
	 *							are slowly filled and then turned into safely
	 *							executable ones with the safety on at the end.
	 * @param	bool   $was_string_literal Was the value part of a template string?
	 * @return	string The new conditional value to use instead of $str.
	 */
	public function encode_conditional_value($value, $safety = FALSE, $was_string_literal = FALSE)
	{
		// It doesn't make sense to allow array values
		if (is_array($value))
		{
			return ' FALSE ';
		}

		// An object that cannot be converted to a string is a problem
		if (is_object($value) && ! method_exists($value, '__toString'))
		{
			return ' FALSE ';
		}

		$value = (string) $value; // ONLY strings please

		// TRUE AND FALSE values are for short hand conditionals,
		// like {if logged_in} and so we have no need to remove
		// unwanted characters and we do not quote it.
		if ($value == 'TRUE' || $value == 'FALSE')
		{
			return ' '.$value.' ';
		}

		// Rules:
		// 1. Encode all non string literals
		// 2. Do not encode embedded tags in strings when safety is FALSE
		// 3. Do not encode braces in strings

		$has_embedded_tag = FALSE;
		$has_embedded_module_tag = FALSE;
		$encode_braces = TRUE;

		if ($was_string_literal)
		{
			$has_embedded_module_tag = (stristr($value, LD.'exp:') && stristr($value, RD));

			if ($has_embedded_module_tag)
			{
				if ($safety === FALSE)
				{
					// See Rule #2
					return ' "' . $value . '" ';
				}
			}
			else
			{
				$has_embedded_tag = (stristr($value, LD) || stristr($value, RD));
			}
		}

		// See Rule #3
		if ($was_string_literal && $has_embedded_tag)
		{
			$encode_braces = FALSE;
		}

		if (strlen($value) > 100)
		{
			$value = substr(htmlspecialchars($value), 0, 100);
		}

		$value = str_replace(
			array("'", '"', '(', ')', '$', "\n", "\r", '\\'),
			array('&#39;', '&#34;', '&#40;', '&#41;', '&#36;', '', '', '&#92;'),
			$value
		);

		if ($encode_braces)
		{
			$value = str_replace(
				array('{', '}',),
				array('&#123;', '&#125;',),
				$value
			);
		}

		// quote it as a proper string
		return ' "' . $value . '" ';
	}
}

class ConditionalException extends Exception {}
class UnsafeConditionalException extends ConditionalException {}
class InvalidConditionalException extends ConditionalException {}