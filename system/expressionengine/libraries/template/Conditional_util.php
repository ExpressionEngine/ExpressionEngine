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
			$prefixed_vars[$prefix.$key] = $this->encode_conditional_value($var, $safety);
		}

		$vars = $prefixed_vars;

		// Get the token stream
		$tokens = $this->extract_conditionals($str, $vars);

		$output = '';
		$condition = '';
		$condition_branch = '';
		$parenthesis_depth = 0;

		foreach ($tokens as $token)
		{
			list($type, $value) = $token;

			switch ($type)
			{
				case 'IF':
					$condition = '';
					$condition_branch = 'if';
					break;
				case 'ELSEIF':
					$condition = '';
					$condition_branch = 'if:elseif';
					break;
				case 'ELSE':
					$output .= '{if:else}';
					break;
				case 'ENDIF':
					$output .= '{/if}';
					break;
				case 'STRING':
					$condition .= $this->encode_conditional_value($value, $safety, TRUE);
					break;
				case 'NUMBER':
					$condition .= ' '.$value.' ';
					break;
				case 'OPERATOR':
					if ($value == '(')
					{
						$parenthesis_depth++;
					}
					elseif ($value == ')')
					{
						$parenthesis_depth--;
					}

					$condition .= $value;
					break;
				case 'VARIABLE':
					$uppercase_value = strtoupper($value);

					if ($uppercase_value == 'TRUE' || $uppercase_value == 'FALSE' ||
						$uppercase_value == 'OR' || $uppercase_value == 'AND' ||
						$uppercase_value == 'XOR')
					{
						$condition .= ' '.$uppercase_value.' ';
					}
					elseif (isset($vars[$value]))
					{
						if (is_bool($vars[$value]))
						{
							$condition .= ($vars[$value]) ? ' TRUE ' : ' FALSE ';
						}
						else
						{
							$condition .= $vars[$value];
						}
					}
					elseif ($safety === FALSE)
					{
						$condition .= ' '.$value.' ';
					}
					else
					{
						$condition .= ' FALSE ';
					}
					break;
				case 'ENDCOND':
					$condition = preg_replace('/\s+/', ' ', $condition);
					$condition = str_replace('FALSE (', 'FALSE && (', $condition);
					$condition = preg_replace('/FALSE(\s+FALSE)+/', 'FALSE', $condition);
					$condition = trim($condition);

					if ($parenthesis_depth < 0)
					{
						$condition = str_repeat('(', -$parenthesis_depth).$condition;
					}
					else if ($parenthesis_depth > 0)
					{
						$condition = $condition.str_repeat(')', $parenthesis_depth);
					}

					$full_tag = '{'.$condition_branch.' '.$condition.'}';

					if ($this->conditional_is_unsafe($full_tag))
					{
						throw new UnsafeConditionalException('Conditional is unsafe.');
					}

					$output .= $full_tag;

					$condition = '';
					$condition_branch = '';
					break;
				case 'MISC':

					// there are also AND and OR which are currently mislabeled
					// as words
					$unsafe_operators = array(
						'/*',
						'//',
						'*/',
						'`'
					);

					foreach ($unsafe_operators as &$operator)
					{
						$operator = preg_quote($operator, '/');
					}

					if (preg_match('/'.implode('|', $unsafe_operators).'/', $value))
					{
						throw new UnsafeConditionalException('Conditional is unsafe.');
					}

					if (trim($value) != '')
					{
						$condition .= ' FALSE ';
					}
					else
					{
						$condition .= ' ';
					}
/*
					$operators = array(
						'||',
						'&&',
						'==',
						'!=',
						'<>',
						'%',
						'+',
						'-',
						'.',
						'<',
						'>',
						'(',
						')',
					);

					foreach ($operators as &$operator)
					{
						$operator = preg_quote($operator, '/');
					}

					$invalid = '';
					$regex = '/^('.implode('|', $operators).'|\s+)/';

					while ($value != '')
					{
						if (preg_match($regex, $value, $match))
						{
							if ($invalid != '')
							{
								$condition .= ' FALSE ';
								$invalid = '';
							}

							$condition .= ''.$match[1].'';

							$value = substr($value, strlen($match[1]));
						}
						elseif ($value != '')
						{
							if ($value == ' ')
							{
								$condition .= ' ';
								$invalid = '';
							}
							else
							{
								$invalid .= $value[0];
							}

							$value = substr($value, 1);
						}
					}

					if ($invalid != '')
					{
						$condition .= ' FALSE ';
					}
*/
					break;
				case 'TEMPLATE_STRING':
					$output .= $value;
					break;
				default:
					$condition .= $value;
			}
		}

		// Unprotect <script> tags
		if ($this->protect_javascript !== FALSE && count($protected_javascript) > 0)
		{
			$output = str_replace(array_keys($protected_javascript), array_values($protected_javascript), $output);
		}

		return $output;
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
		$available_tokens = array(
			'TEMPLATE_STRING',	// generic
			'IF',				// {if
			'ELSE',				// {if:else
			'ELSEIF',			// {if:elseif
			'ENDIF',			// {/if}
			'ENDCOND',			// } at the end of an if
			'COND_VAR',			// Variable as used in conditionals (no wrapping curlies)
			'STRING',			// literal string "foo", or 'foo'. The value does not include quotes
			'OPERATOR',
			'MISC',				// other stuff such as operators, whitespace, and numbers
		);

		$tokens = array();


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

			'C_WHITE',	'C_NOT',	'C_DQUOTE',	'C_HASH',	'C_DOLLAR',	'C_MOD',	'C_AMP',	'C_SQUOTE',
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

		$end = 0;
		$closest_closing = INF;

		while (($i = strpos($str, '{if', $i)) !== FALSE)
		{
			if ($i > $closest_closing)
			{
				$before = substr($str, $end, $closest_closing - $end);
				$after = substr($str, $closest_closing + 5, $i - $closest_closing - 5);

				if ($before != '')
				{
					$tokens[] = array('TEMPLATE_STRING', $before);
				}

				$tokens[] = array('ENDIF', '{/if}');

				if ($after != '')
				{
					$tokens[] = array('TEMPLATE_STRING', $after);
				}
			}
			elseif ($i > $end)
			{
				$tokens[] = array('TEMPLATE_STRING', substr($str, $end, $i - $end));
			}


			$start   = $i;
			$buffer  = '';
			$state   = 'OK';
			$curlies = 0;

			// Skip past the "{if"
			$i = $i + 3;

			// A valid {if is either followed by whitespace or by a colon.
			// The easiest way to check that is to find the class this character
			// belongs to.

			$chr = ord($str[$i]);
			$char_name = ($chr >= 128) ? 'C_ABC' : $ascii_map[$chr];

			// If the "{if" is not followed by whitespace this might be a
			// variable (i.e. {iffy}) or an "{if:else..." conditional
			if ($char_name != 'C_WHITE')
			{
				if ($char_name != 'C_COLON')
				{
					// valid variable, but not a conditional
				//	$end = $i;
					continue;
				}

				// Substringing lets us use an anchored regex below
				$substr = substr($str, $i, 10);

				// This is an invalid conditional because "{if:" is reserved
				// for conditionals.
				if (preg_match('/^:else(\s?}|if\s)/', $substr, $matches) != 1)
				{
					throw new InvalidConditionalException('Conditional is invalid: "{if:" is reserverd for conditionals.');
				}

				// Skip past any :else or :elseif
				$i += strlen($matches[0]);

				// if it's an else, not an elseif, then it won't have a body,
				// so we don't need to do any processing on it.
				if (trim($matches[1]) == '}')
				{
					$end = $i;
					$tokens[] = array('ELSE', '{if:else}');
					continue;
				}

				$tokens[] = array('ELSEIF', '{if:elseif ');
			}
			else
			{
				$tokens[] = array('IF', '{if ');
			}

			// No sense continuing if we cannot find a {/if}
			$closest_closing = strpos($str, '{/if}', $i);

			if ($closest_closing === FALSE)
			{
				throw new InvalidConditionalException('Conditional is invalid: missing a "{/if}".');
			}

			while ($i < $str_length)
			{
				// Grab the new character and save the old state.
				$char = $str[$i];
				$old_state = $state;

				// If it's an ascii character we get its name from the ascii
				// map, otherwise we simply assume that it's safe for strings.
				// This should hold true because all control characters and php
				// operators are in the ascii map.
				$chr = ord($char);
				$char_class = ($chr >= 128) ? 'C_ABC' : $ascii_map[$chr];

				// Don't bother with control characters.
				if ($char_class == '__')
				{
					$i++;
					continue;
				}

				// If an edge exists, we transition. Otherwise we stay in
				// our current state.
				if (isset($edges[$char_class]))
				{
					$edge  = $edges[$char_class];
					$state = $transitions[$old_state][$edge];
				}

				// Track variables
				if ($state == 'VAR' || $state == 'NUM')
				{
					if ($old_state != 'NUM' && $old_state != 'VAR')
					{
						$tokens[] = array('MISC', $buffer);
						$buffer = '';
					}

					// Manually transition out of state and store the buffer
					if ($char_class != 'C_ABC' && $char_class != 'C_DIGIT' &&
						$char_class != 'C_COLON' && $char_class != 'C_MINUS')
					{
						if ($state == 'VAR')
						{
							$tokens[] = array('VARIABLE', $buffer);
						}
						else
						{
							$tokens[] = array('NUMBER', $buffer);
						}

						$buffer = '';
						$state = 'OK';
					}
				}

				if ($state == 'OK')
				{
					$operators = array(
						'\(', '\)',
						'\|\|', '&&',
						'==', '!=', '<=', '>=', '<>', '<', '>',
						'%', '\+', '-',
						'\.',
					);

					$temp = substr($str, $i, 5);

					$invalid = '';
					$regex = '/^('.implode('|', $operators).')/';

					if (preg_match($regex, $temp, $match))
					{
						if ($buffer != '')
						{
							$tokens[] = array('MISC', $buffer);
						}

						$i += strlen($match[1]);

						// If the next character is the same as the last one in
						// this match then we have a weird repetition that we do
						// not allow: >>>, ===, !==, <<, etc.
						if ($str[$i] == $match[1][0] && $str[$i] != ')' && $str[$i] != '(')
						{
							$tokens[] = array('MISC', $match[1].$str[$i]);
							$i++;
						}
						else
						{
							$tokens[] = array('OPERATOR', $match[1]);
						}


						$buffer = '';
						continue;
					}
				}


				// Checking for balanced curly braces
				if ($state == 'RD')
				{
					if ($curlies == 0)
					{
						$i++;
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
					$tokens[] = array('STRING', $buffer);

					$state = 'OK';
					$buffer = '';
					$i++;
					continue; // do not put trailing quotes in the buffer
				}

				// END Events

				// Handle buffers
				if (($state == 'SS' || $state == 'SD') && $state != $old_state)
				{
					// reset the buffer if we're starting a string
					if ($buffer != '')
					{
						$tokens[] = array('MISC', $buffer);
					}

					$buffer = '';
				}
				else
				{
					$buffer .= $char;
				}

				$i++;
			}

			// Not in an end state, or curly braces are unbalanced, "error" out
			if ($state != 'END' || $curlies != 0)
			{
				throw new InvalidConditionalException('Conditional is invalid: not in an end state or unbalanced curly braces.');
			}

			// Handle any buffer contents from before we hit the closing brace
			if ($buffer != '')
			{
				switch ($old_state)
				{
					case 'VAR': $tokens[] = array('VARIABLE', $buffer);
						break;
					case 'NUM': $tokens[] = array('NUMBER', $buffer);
						break;
					default:	$tokens[] = array('MISC', $buffer);
						break;
				}
			}

			$tokens[] = array('ENDCOND', '}');

			$end = $i;
		}

		// Find any leftover closing tags
		while ($closest_closing = strpos($str, '{/if}', $end))
		{
			$before = substr($str, $end, $closest_closing - $end);

			if ($before != '')
			{
				$tokens[] = array('TEMPLATE_STRING', $before);
			}

			$tokens[] = array('ENDIF', '{/if}');

			$end += ($closest_closing - $end) + 5;
		}

		// Grab the rest of the template
		if ($str_length > $end)
		{
			$tokens[] = array('TEMPLATE_STRING', substr($str, $end, $str_length - $end));
		}

		return $tokens;
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