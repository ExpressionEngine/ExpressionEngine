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

		list($str, $conditionals) = $this->extract_conditionals($str, $vars);

		// Check conditionals for unsafe characters and mark any conditionals
		// strings that potential contained EE tags
		foreach ($conditionals as $condition)
		{
			if ($this->conditional_is_unsafe($condition['full_open_tag']))
			{
				throw new UnsafeConditionalException('Conditional is unsafe.');
			}
		}

		// Encode the conditional strings
		foreach ($conditionals as $i => $condition)
		{
			foreach ($condition['strings'] as $key => $value)
			{
				$conditionals[$i]['strings'][$key] = $this->encode_conditional_value($value, $safety, TRUE);
			}
		}

		// Encode the user variables and add a prefix if given
		$prefixed_vars = array();

		foreach ($vars as $key => $var)
		{
			$prefixed_vars[$prefix.$key] = $this->encode_conditional_value($var, $safety);
		}

		$vars = $prefixed_vars;

		foreach ($conditionals as $conditional)
		{
			$condition_vars = array_merge($vars, $conditional['strings']);
			$condition		= $conditional['condition'];
			$full_open_tag 	= $conditional['full_open_tag'];

			$orig_condition	= $condition; // we save this so we can replace it later.

			$done = array();

			if ( ! in_array($full_open_tag, $done))
			{
				$done[] = $full_open_tag;

				// Now we parse the conditional looking for things we do
				// want. This should keep our conditionals safe and free
				// of arbitrary code execution.

				// This will show us how PHP will view the conditional.
				$prelim_tokens = token_get_all('<?php ' . $condition . '?>');

				// Remove the opening and closing PHP tags
				$prelim_tokens = array_slice($prelim_tokens, 1, count($prelim_tokens) - 2);

				$buffer = '';

				$parenthesis_depth = 0;

				// We need to do two passes. The first one is to catch EE's more
				// esoteric variable naming strategy, where dashes are allowed for
				// variable characters, negative signs, and subtraction. This loop
				// will collapse any valid combinations that are interpreted as
				// variables by the parser.
				$tokens = array();

				$collapse = '';

				foreach ($prelim_tokens as $token)
				{
					if ($collapse !== '' && ($token === '-' || $token === ':'))
					{
						$collapse .= $token;
					}
					elseif (is_array($token) && in_array($token[0], array(T_STRING, T_LNUMBER, T_DNUMBER)))
					{
						$collapse .= $token[1];
					}
					else
					{
						if (trim($collapse, '-') !== '')
						{
							$tokens[] = array(
								is_numeric($collapse) ? T_LNUMBER : T_STRING,
								$collapse
							);

							$collapse = '';
						}

						$tokens[] = $token;
					}
				}

				if ($collapse !== '')
				{
					$tokens[] = array(
						is_numeric($collapse) ? T_LNUMBER : T_STRING,
						$collapse
					);
				}

				// We will now parse for allowed tokens, the rest are either
				// stripped or converted to FALSE
				foreach ($tokens as $token)
				{
					// Some elements of the $tokens array are single
					// characters. We account for those here.
					if ( ! is_array($token))
					{
						switch ($token)
						{
							case '-':
							case '+':
							case '<':
							case '>':
							case '.':
							case '%':
								break;
							case '(': $parenthesis_depth++;
								break;
							case ')': $parenthesis_depth--;
								break;
							default:
								if ($safety === TRUE)
								{
									$buffer .= ' FALSE ';
									continue 2; // other tokens don't get anything
								}
						}

						$buffer .= $token;
					}
					else
					{
						switch ($token[0])
						{
							case T_CONSTANT_ENCAPSED_STRING:
							case T_WHITESPACE:
							case T_BOOLEAN_AND:
							case T_BOOLEAN_OR:
							case T_LOGICAL_AND:
							case T_LOGICAL_OR:
							case T_LOGICAL_XOR:
							case T_LNUMBER:
							case T_DNUMBER:
							case T_IS_EQUAL:
							case T_IS_GREATER_OR_EQUAL:
							// This is new functionality for conditionals
							// (=== operator) so I am disabling it for now
							// (SCB 5-5-2014)
							// case T_IS_IDENTICAL:
							// case T_IS_NOT_IDENTICAL:
							case T_IS_NOT_EQUAL:
							case T_IS_SMALLER_OR_EQUAL:
								$buffer .= $token[1];
								break;

							case T_STRING:
								$value = $token[1];
								$uppercase_value = strtoupper($value);

								if ($uppercase_value == 'TRUE' || $uppercase_value == 'FALSE')
								{
									$buffer .= $uppercase_value;
									break;
								}
								elseif (isset($condition_vars[$value]))
								{
									if (is_bool($condition_vars[$value]))
									{
										$buffer .= ($condition_vars[$value]) ? "TRUE" : "FALSE";
									}
									else
									{
										$buffer .= $condition_vars[$value];
									}
									break;
								}

							default:
								if ($safety === TRUE)
								{
									$buffer .= ' FALSE ';
									if ($this->debug === TRUE)
									{
										trigger_error('Unset EE Conditional Variable ('.$token[1].') : '.$full_open_tag,
													  E_USER_WARNING);
									}
								}
								else
								{
									$buffer .= $token[1];
								}
						}
					}
				}

				if ($parenthesis_depth < 0)
				{
					$buffer = str_repeat('(', -$parenthesis_depth).$buffer;
				}
				else if ($parenthesis_depth > 0)
				{
					$buffer = $buffer.str_repeat(')', $parenthesis_depth);
				}

				$buffer = str_replace('FALSE (', 'FALSE && (', $buffer);
				$buffer = preg_replace('/FALSE(\s+FALSE)+/', 'FALSE', $buffer);

				$condition = $buffer;
			}

			$condition = trim($condition);

			$new_open_tag = str_replace($orig_condition, $condition, $full_open_tag);
			$new_open_tag = preg_replace('/\s+/s', ' ', $new_open_tag);

			$str = str_replace($full_open_tag, $new_open_tag, $str);
		}

		// Unprotect <script> tags
		if ($this->protect_javascript !== FALSE && count($protected_javascript) > 0)
		{
			$str = str_replace(array_keys($protected_javascript), array_values($protected_javascript), $str);
		}

		unset($switch);
		unset($protect);

		return $str;
	}

	/**
	 * Finds conditionals with quoted strings and turns the strings
	 * into variables. This lets us later find the boundaries of the
	 * conditional without worry and protects us from escape characters
	 * and nested quotes in our conditionals.
	 *
	 * @param $str The template chunk to look through
	 * @param $vars Any variables that will be in the conditional
	 * @return Array [new chunk, new variables]
	 */
	public function extract_conditionals($str, $vars)
	{
		// start at the beginning
		$i = 0;
		$str_length = strlen($str);

		$var_count = 0;
		$found_conditionals = array();

		// We use a finite state machine to walk through
		// the conditional and find the correct closing
		// bracket.
		//
		// The following arrays describe the state machine as
		// a list of character classes, edges, and transitions.


		// An array of 128 elements, one for each ascii character at its ordinal
		// index. We use this to define character classes.
		//
		// For example, all of these will result in C_WHITE:
		//
		// $ascii_map[ord(' ')]
		// $ascii_map[ord("\n")]
		// $ascii_map[ord("\t")]
		// $ascii_map[ord("\r")]

		$ascii_map = array(
			'__',		'__',		'__',		'__',		'__',		'__',		'__',		'__',
			'__',		'C_WHITE',	'C_WHITE',	'__',		'__',		'C_WHITE',	'__',		'__',
			'__',		'__',		'__',		'__',		'__',		'__',		'__',		'__',
			'__',		'__',		'__',		'__',		'__',		'__',		'__',		'__',

			'C_SPACE',	'C_NOT',	'C_DQUOTE',	'C_HASH',	'C_DOLLAR',	'C_MOD',	'C_AMP',	'C_SQUOTE',
			'C_LPAREN',	'C_RPAREN',	'C_STAR',	'C_PLUS',	'C_ETC',	'C_MINUS',	'C_POINT',	'C_SLASH',
			'C_DIGIT',	'C_DIGIT',	'C_DIGIT',	'C_DIGIT',	'C_DIGIT',	'C_DIGIT',	'C_DIGIT',	'C_DIGIT',
			'C_DIGIT',	'C_DIGIT',	'C_COLON',	'C_SMICOL',	'C_LT',		'C_EQ',		'C_GT',		'C_QUESTION',

			'C_ETC',	'C_ABC',	'C_ABC',	'C_ABC',	'C_ABC',	'C_ABC',	'C_ABC',	'C_ABC',
			'C_ABC',	'C_ABC',	'C_ABC',	'C_ABC',	'C_ABC',	'C_ABC',	'C_ABC',	'C_ABC',
			'C_ABC',	'C_ABC',	'C_ABC',	'C_ABC',	'C_ABC',	'C_ABC',	'C_ABC',	'C_ABC',
			'C_ABC',	'C_ABC',	'C_ABC',	'C_LSQRB',	'C_BACKS',	'C_RSRQB',	'C_HAT',	'C_ABC', // underscore is a letter for our needs

			'C_BTICK',	'C_ABC',	'C_ABC',	'C_ABC',	'C_ABC',	'C_ABC',	'C_ABC',	'C_ABC',
			'C_ABC',	'C_ABC',	'C_ABC',	'C_ABC',	'C_ABC',	'C_ABC',	'C_ABC',	'C_ABC',
			'C_ABC',	'C_ABC',	'C_ABC',	'C_ABC',	'C_ABC',	'C_ABC',	'C_ABC',	'C_ABC',
			'C_ABC',	'C_ABC',	'C_ABC',	'C_LD',		'C_PIPE',	'C_RD',		'C_ETC',	'C_ETC'
		);

		// Hitting an edge causes a transition to happen. The edges are
		// named after the ascii group that causes the transition.

		$edges = array(
			'C_BACKS'	=> 0,	// \
			"C_SQUOTE"	=> 1,	// '
			'C_DQUOTE'	=> 2,	// "
			'C_LD'		=> 3,	// {
			'C_RD'		=> 4,	// },
			'C_ABC'		=> 5,	// letters
			'C_DIGIT'	=> 6,	// numbers
			'C_MINUS'	=> 8,	// -
			'C_COLON'	=> 8,	// :
		);

		// Hitting an edge triggers a lookup in the transition table to see
		// if the current state needs to change.

		// Some notes on these transitions:
		//
		// • Numbers can transition to variables, but variables can never transition
		//   to numbers. So if we're in a variable state, then we remain there.
		// • A period in a number state currently transitions back to an OK state
		//   since we don't want the above rule to trigger variables with dots in them
		//
		// Potential error transitions:
		// (currently transition to OK and get caught later)
		//
		// NUM + : -> ERR
		// OK +  : -> ERR

		// Available States:
		//
		// Any labelled as events do not have transitions of their own and are
		// handled in the loop directly.
		//
		//		OK	- default
		//		SS	- string single 'str'
		//		SD	- string double "str"
		//		VAR - inside a variable
		//		NUM	- inside a number
		//		ESC	- \escaped				[event]
		//		LD	- {						[event]
		//		RD	- }						[event]
		//		EOS	- end of string			[event]
		//		END	- done					[event]

		$transitions = array(// \	'		"		{		}		ABC		DIGIT	-		:	indexes match $edges
			'OK'	=> array('ESC',	'SS',	'SD',	'LD',	'RD',	'VAR',	'NUM',	'OK',	'OK'),
			'SS'	=> array('ESC',	'EOS',	'SS',	'SS',	'SS',	'SS',	'SS',	'SS',	'SS'),
			'SD'	=> array('ESC',	'SD',	'EOS',	'SD',	'SD',	'SD',	'SD',	'SD',	'SD'),
			'VAR'	=> array('ESC',	'SS',	'SD',	'LD',	'RD',	'VAR',	'VAR',	'VAR',	'VAR'),
			'NUM'	=> array('ESC',	'SS',	'SD',	'LD',	'RD',	'VAR',	'NUM',	'OK',	'OK'),
		);

		$rand = md5(uniqid(mt_rand()));

		while (($i = strpos($str, '{if', $i)) !== FALSE)
		{
			$start   = $i;
			$buffer  = '';
			$state   = 'OK';
			$curlies = 0;

			// Confirm this is a conditional and not some other tag
			$char = $str[$i + 3];

			// If the "{if" is not followed by whitespace this might be a
			// variable (i.e. {iffy}) or an "{if:else..." conditional
			if ( ! ($char == ' ' || $char == "\t" || $char == "\n" || $char == "\r" ))
			{
				if ($char == ':')
				{
					$substr = substr($str, $i + 3, 10);

					// This is an invalid conditional because "{if:" is reserved
					// for conditionals.
					if (preg_match('/^:else(\s?}|if\s)/', $substr, $matches) != 1)
					{
						throw new InvalidConditionalException('Conditional is invalid: "{if:" is reserverd for conditionals.');
					}

					// if it's an else, not an elseif, then it won't have a body,
					// so we don't need to do any processing on it.
					if (trim($matches[1]) == '}')
					{
						$i += 3;
						continue;
					}

					$i += strlen($matches[0]);
				}
				else
				{
					// valid variable, but not a conditional
					$i += 3;
					continue;
				}
			}
			else
			{
				$i += 3;
			}

			// No sense continuing if we cannot find a {/if}
			if (strpos($str, '{/if}', $i + 3) === FALSE)
			{
				throw new InvalidConditionalException('Conditional is invalid: missing a "{/if}".');
			}


			$variables = array();
			$string_literal_values = array();
			$quoted_string_literals = array();
			$string_literal_placeholders = array();

			while ($i < $str_length)
			{
				// Grab the new character and save the old state.
				$char = $str[$i++];
				$old_state = $state;

				// If it's an ascii character we get its name from the ascii
				// map, otherwise we simply assume that it's safe for strings.
				// This should hold true because all control characters and php
				// operators are in the ascii map.
				$chr = ord($char);
				$edge_name = ($chr >= 128) ? 'C_ABC' : $ascii_map[$chr];

				// If the edge exists, we transition. Otherwise we stay in
				// our current state.
				if (isset($edges[$edge_name]))
				{
					$edge  = $edges[$edge_name];
					$state = $transitions[$old_state][$edge];
				}

				// Track variables
				if ($state == 'VAR' || $state == 'NUM')
				{
					// Manually transition out of state and store the buffer
					if ($edge_name != 'C_ABC' && $edge_name != 'C_DIGIT' &&
						$edge_name != 'C_COLON' && $edge_name != 'C_MINUS')
					{
						if ($state == 'VAR')
						{
							$variables[] = $buffer;
						}

						$buffer = '';
						$state = 'OK';
					}
					else
					{
						$buffer .= $char;
					}
				}


				// Checking for balanced curly braces
				if ($state == 'RD')
				{
					if ($curlies == 0)
					{
						$state = 'END';
						break;
					}

					$curlies--;
					$state = 'OK';
				}
				elseif ($state == 'LD')
				{
					$curlies++;
					$state = 'OK';
				}


				// On escape, store char and restore previous state
				if ($state == 'ESC')
				{
					$buffer .= $char;
					$char = $str[$i++];
					$state = $old_state; // pretend nothing happened
				}

				// Hitting the end of a string must mean we're back to an OK
				// state, so store the string in a variable and reset
				elseif ($state == 'EOS')
				{
					$string_literal_values[] = stripslashes($buffer);
					$quoted_string_literals[] = $char.$buffer.$char;

					$var_count++;
					$string_literal_placeholders[] = 'var_'.$rand.$var_count;

					$state = 'OK';
					$buffer = '';
				}

				// END Events

				// Handle buffers
				if ($state == 'SS' || $state == 'SD')
				{
					if ($state == $old_state)
					{
						$buffer .= $char;
					}
					else
					{
						$buffer = '';
					}
				}
			}

			// Not in an end state, or curly braces are unbalanced, "error" out
			if ($state != 'END' || $curlies != 0)
			{
				throw new InvalidConditionalException('Conditional is invalid: not in an end state or unbalanced curly braces.');
			}

			$end = $i;

			// Extract the full conditional
			$strings = array();
			$full_conditional = substr($str, $start, $end - $start);

			// If we found strings, we replace the fully matched conditional
			// with one that has placeholders instead of any of the strings.
			if (count($string_literal_placeholders))
			{
				$full_conditional = str_replace($quoted_string_literals, $string_literal_placeholders, $full_conditional);
				$str = substr_replace($str, $full_conditional, $start, $end - $start);

				// Adjust our while loop conditions
				$new_length = strlen($str);
				$i = $i + ($new_length - $str_length);
				$str_length = $new_length;

				$strings = array_combine($string_literal_placeholders, $string_literal_values);
			}

			// TODO this can be sped up by incorporating the valid conditional check above
			$condition = preg_replace('/(^'.preg_quote(LD).'((if:else)*if)\s+|'.preg_quote(RD).'$)/s', '', $full_conditional);

			// Save the conditional for further processing.
			$found_conditionals[] = array(
				'full_open_tag'	=> $full_conditional,
				'condition'		=> $condition,
				'strings'		=> $strings,
				'variables'		=> $variables
				// future: numbers, operators?
			);
		}

		return array($str, $found_conditionals);
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
			return 'FALSE';
		}

		// An object that cannot be converted to a string is a problem
		if (is_object($value) && ! method_exists($value, '__toString'))
		{
			return 'FALSE';
		}

		$value = (string) $value; // ONLY strings please

		// TRUE AND FALSE values are for short hand conditionals,
		// like {if logged_in} and so we have no need to remove
		// unwanted characters and we do not quote it.
		if ($value == 'TRUE' || $value == 'FALSE')
		{
			return $value;
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
					return '"' . $value . '"';
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
		return '"' . $value . '"';
	}
}

class ConditionalException extends Exception {}
class UnsafeConditionalException extends ConditionalException {}
class InvalidConditionalException extends ConditionalException {}