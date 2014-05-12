<?php

class Conditional_lexer {

	/**
	 * The main token array
	 */
	private $tokens;

	/**
	 * Available tokens
	 */
	private $token_names = array(
		'TEMPLATE_STRING',	// generic
		'IF',				// {if
		'ELSE',				// {if:else
		'ELSEIF',			// {if:elseif
		'ENDIF',			// {/if}
		'ENDCOND',			// } at the end of an if
		'STRING',			// literal string "foo", or 'foo'. The value does not include quotes
		'NUMBER',
		'VARIABLE',
		'OPERATOR',
		'MISC',				// other stuff such as operators, whitespace, and numbers
	);

	private $operators = array(
		'(', ')',
		'||', '&&',
		'==', '!=', '<=', '>=', '<>', '<', '>',
		'%', '+', '-',
		'.',
	);

	private $token_values;

	private $ascii_map = array();
	private $patterns = array();

	public function __construct()
	{
		$this->token_values = array_flip($this->token_names);

		$this->patterns['operators'] = $this->getOperatorRegex();

		// An array of 128 elements, one for each ascii character at its ordinal
		// index. We use this to define character classes.
		//
		// For example, all of these will result in C_WHITE:
		//
		// $this->ascii_map[ord(' ')]
		// $this->ascii_map[ord("\n")]
		// $this->ascii_map[ord("\t")]
		// $this->ascii_map[ord("\r")]

		$this->ascii_map = array(
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
	}


	/**
	 * Finds conditionals an returns a token stream for the entire template, with
	 * conditional specific tokens.
	 *
	 * @param $str The template chunk to look through
	 * @return Array [new chunk, new variables]
	 */
	public function tokenize($str)
	{
		$this->tokens = array();

		// We use a finite state machine to walk through
		// the conditional and find the correct closing
		// bracket.
		//
		// The following arrays describe the state machine as
		// a list of character classes, edges, and transitions.


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
			'C_MINUS'	=> 7,	// -
			'C_COLON'	=> 8,	// :
			'C_POINT'	=> 9,	// .
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
		// FLOAT + . -> ERR
		// FLOAT + : -> ERR

		// Available States:
		//
		// Any labelled as events do not have transitions of their own and are
		// handled in the loop directly.
		//
		//		OK		- default
		//		SS		- string single 'str'
		//		SD		- string double "str"
		//		VAR 	- inside a variable
		//		NUM		- inside a number
		//		POINT	- ambiguous point
		//		MINUS	- ambiguous minus
		//		FLOAT	- inside a floating point number
		//		ESC		- \escaped				[event]
		//		LD		- {						[event]
		//		RD		- }						[event]
		//		EOS		- end of string			[event]
		//		END		- done					[event]

		$transitions = array(// \	'		"		{		}		ABC		DIGIT	-		:		.	indexes match $edges
			'OK'	=> array('ESC',	'SS',	'SD',	'LD',	'RD',	'VAR',	'NUM',	'MINUS',	'ERR',	'POINT'),
			'SS'	=> array('ESC',	'EOS',	'SS',	'SS',	'SS',	'SS',	'SS',	'SS',	'SS',	'SS'),
			'SD'	=> array('ESC',	'SD',	'EOS',	'SD',	'SD',	'SD',	'SD',	'SD',	'SD',	'SD'),
			'VAR'	=> array('ESC',	'SS',	'SD',	'LD',	'RD',	'VAR',	'VAR',	'MINUS',	'VAR',	'OK'),
			'NUM'	=> array('ESC',	'SS',	'SD',	'LD',	'RD',	'VAR',	'NUM',	'MINUS',	'ERR',	'POINT'),
			'FLOAT'	=> array('ESC',	'SS',	'SD',	'LD',	'RD',	'VAR',	'FLOAT','OK',	'ERR',	'ERR'),
		);

		$this->str = $str;

		while ($this->str != '')
		{
			// go to the next LD/RD
			$buffer = $this->seekTo('{}');

			// anything we hit in the meantime is template string
			$this->addToken('TEMPLATE_STRING', $buffer);

			// handle closing if's
			if ($this->peek(5) == '{/if}')
			{
				$this->addToken('ENDIF', $this->move(5));
				continue;
			}

			// potential opening ifs
			$potential_if = $this->peekRegex('{if(:else(\s?}|if\s)|\s)');

			switch (trim($potential_if))
			{
				case "{if":
					$this->addToken('IF', $this->move(strlen($potential_if)));
					break;;
				case "{if:else}":
					$this->addToken('ELSE', $this->move(strlen($potential_if)));
					continue 2;
				case "{if:elseif":
					$this->addToken('ELSEIF', $this->move(strlen($potential_if)));
					break;
				default:
					// something else
					if ($this->peek(4) == '{if:') // sanity check, could be in a better place
					{
						throw new InvalidConditionalException('Conditional is invalid: "{if:" is reserverd for conditionals. Found: ' . $potential_if);
					}

					$this->addToken('TEMPLATE_STRING', $this->next());
					continue 2;
			}

			// No sense continuing if we cannot find a {/if}
			if (strpos($this->str, '{/if}') === FALSE)
			{
				throw new InvalidConditionalException('Conditional is invalid: missing a "{/if}".');
			}

			$buffer  = '';
			$state   = 'OK';
			$curlies = 0;

			while ($this->str != '')
			{
				$char = $this->next();

				// Save the old state.
				$old_state = $state;

				// If it's an ascii character we get its name from the ascii
				// map, otherwise we simply assume that it's safe for strings.
				// This should hold true because all control characters and php
				// operators are in the ascii map.
				$chr = ord($char);
				$char_class = ($chr >= 128) ? 'C_ABC' : $this->ascii_map[$chr];

				// Don't bother with control characters.
				if ($char_class == '__')
				{
					continue;
				}

				// If an edge exists, we transition. Otherwise we stay in
				// our current state.
				if (isset($edges[$char_class]))
				{
					$edge  = $edges[$char_class];
					$state = $transitions[$old_state][$edge];
				}

				if ($state == 'ERR')
				{
					throw new InvalidConditionalException('In an ERROR state. Buffer: '.$buffer.$char);
				}

				if ($state == 'POINT')
				{
					$next_chr = ord($this->peek());
					$next_char_class = ($next_chr >= 128) ? 'C_ABC' : $this->ascii_map[$next_chr];

					// We may may be in a FLOAT state
					if ($next_char_class == 'C_DIGIT')
					{
						$state = 'FLOAT';
					}
					else
					{
						$state = 'OK';
					}
				}

				// Manually handle "int-alpha" variables and negative numbers
				if ($state == 'MINUS')
				{
					$next_chr = ord($this->peek());
					$next_char_class = ($next_chr >= 128) ? 'C_ABC' : $this->ascii_map[$next_chr];

					if (($old_state == 'VAR' || $old_state == 'NUM') && $next_char_class == 'C_ABC')
					{
						$state = 'VAR';
					}
					elseif ($old_state == 'OK' && $next_char_class == 'C_DIGIT')
					{
						$state = 'NUM';
					}
					else
					{
						$state = 'OK';
					}
				}

				// Track variables
				if ($state == 'VAR' || $state == 'NUM')
				{
					if ($old_state != 'VAR' && $old_state != 'NUM')
					{
						$token_type = in_array($buffer, $this->operators) ? 'OPERATOR' : 'MISC';
						$this->addToken($token_type, $buffer);
						$buffer = '';
					}

					// Manually transition out of state and store the buffer
					if ($char_class != 'C_ABC' && $char_class != 'C_DIGIT' &&
						$char_class != 'C_COLON' && $char_class != 'C_MINUS')
					{
						if ($state == 'VAR')
						{

							$this->addToken('VARIABLE', $buffer);
						}
						else
						{

							$this->addToken('NUMBER', $buffer);
						}

						$buffer = '';
						$state = 'OK';
					}
				}

				if ($state == 'OK')
				{
					// $this->next() consumes a character, but we need it for
					// this operator check
					$this->str = $char.$this->str;

					$regex = $this->patterns['operators'];

					$operator = $this->peekRegex($regex);

					if ($operator != '')
					{
						// Found something, but first save the buffer.

						$oplength = strlen($operator);
						$operator = $this->move($oplength);

						$char = $this->peek();

						// check for invalid ===, <<, etc
						// todo this needs work
						if ($char == $operator[$oplength - 1] && $char != ')' && $char != '(')
						{
							$tokens[] = $this->addToken('MISC', $buffer.$operator.$char);
							$this->next();
						}
						else
						{
							switch ($old_state)
							{
								case "VAR": $token_type = 'VARIABLE';
									break;
								case "NUM": $token_type = 'NUMBER';
									break;
								default: $token_type = 'MISC';
									break;
							}
							$this->addToken($token_type, $buffer);
							$this->addToken('OPERATOR', $operator);
						}

						$buffer = '';
						continue;
					}
					else
					{
						// Remove the character we put back; we didn't find
						// anything
						$this->next();
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
					$char = $this->next();
					$state = $old_state; // pretend nothing happened
				}

				// Hitting the end of a string must mean we're back to an OK
				// state, so store the string in a variable and reset
				elseif ($state == 'EOS')
				{
					$this->addToken('STRING', $buffer);

					$state = 'OK';
					$buffer = '';
					continue; // do not put trailing quotes in the buffer
				}

				// END Events

				// Handle buffers
				if (($state == 'SS' || $state == 'SD') && $state != $old_state)
				{
					// reset the buffer if we're starting a string
					$this->addToken('MISC', $buffer);
					$buffer = '';
				}
				else
				{
					$buffer .= $char;
				}
			}

			// Not in an end state, or curly braces are unbalanced, "error" out
			if ($state != 'END' || $curlies != 0)
			{
				throw new InvalidConditionalException('Conditional is invalid: not in an end state or unbalanced curly braces. State is '.$state.'. Curly count is '.$curlies.'.');
			}

			// Handle any buffer contents from before we hit the closing brace
			if ($buffer != '')
			{
				switch ($old_state)
				{
					case 'VAR': $this->addToken('VARIABLE', $buffer);
						break;
					case 'NUM': $this->addToken('NUMBER', $buffer);
						break;
					case 'FLOAT': $this->addToken('NUMBER', $buffer);
						break;
					default:	$this->addToken('MISC', $buffer);
						break;
				}
			}

			$this->addToken('ENDCOND', '}');

		}

		$this->addToken('TEMPLATE_STRING', $this->str);

		return $this->tokens;
	}

	// get token name. For the future where we don't put strings in as the token name.
	public function getTokenName($int)
	{
		return $this->token_names[$int];
	}

	// add token to the token stream
	public function addToken($type, $value)
	{
		// Always store strings, even empty ones
		if ($type == 'STRING' || $value != '')
		{
			$this->tokens[] = array($type, $value);
		}
	}

	// peek ahead n characters without moving
	private function peek($n = 1)
	{
		return substr($this->str, 0, $n);
	}

	// peek ahead on an anchored regex
	private function peekRegex($regex)
	{
		if (preg_match('/^'.$regex.'/s', $this->str, $matches))
		{
			return $matches[0];
		}

		return '';
	}

	// Seek to the first character in char mask
	private function seekTo($charMask)
	{
		$n = strcspn($this->str, $charMask);
		return $this->move($n);
	}

	// move to the next character
	private function next()
	{
		return $this->move(1);
	}

	// Move ahead n characters in the string, returning the consumed bit
	private function move($n)
	{
		$buffer = substr($this->str, 0, $n);
		$this->str = substr($this->str, $n);

		return $buffer;
	}

	// get operator regex in a peekRegex compatible format
	private function getOperatorRegex()
	{
		$operators = array();

		foreach ($this->operators as $operator)
		{
			$operators[] = preg_quote($operator, '/');
		}

		return '('.implode('|', $operators).')';
	}
}