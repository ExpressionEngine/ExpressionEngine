<?php

namespace EllisLab\ExpressionEngine\Library\Parser\Conditional;

use EllisLab\ExpressionEngine\Library\Parser\AbstractLexer;
use EllisLab\ExpressionEngine\Library\Parser\Conditional\Exception\ConditionalLexerException;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.9.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Conditional Lexer Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class ConditionalLexer extends AbstractLexer {

	/**
	 * The main token array
	 *
	 * Available tokens:
	 *
	 * private $token_names = array(
	 * 	'TEMPLATE_STRING',	// generic
	 * 	'IF',				// {if
	 * 	'ELSE',				// {if:else
	 * 	'ELSEIF',			// {if:elseif
	 * 	'ENDIF',			// {/if}
	 * 	'ENDCOND',			// } at the end of an if
	 * 	'STRING',			// literal string "foo", or 'foo'. The value does not include quotes
	 * 	'NUMBER',			// literal number
	 * 	'VARIABLE',
	 * 	'OPERATOR',			// an operator from the $operators array
	 * 	'MISC',				// other stuff, usually illegal when safety on
	 * 	'LP',				// (
	 * 	'RP',				// )
	 * 	'WHITESPACE',		// \s\r\n\t
	 * 	'BOOL',				// TRUE or FALSE (case insensitive)
	 * 	'TAG',				// {exp:foo:bar}
	 * 	'EOS'				// end of string
	 * );
	 */
	private $tokens;

	/**
	 * The state stack
	 */
	private $stack;

	/**
	 * The current state / top of the stack
	 */
	private $patterns = array(
		'variable'	=> '\w*([a-zA-Z]([\w:-]+\w)?|(\w[\w:-]+)?[a-zA-Z])\w*',
		'number'	=> '-?([0-9]*\.[0-9]+|[0-9]+\.[0-9]*|[0-9]+)'
	);

	/**
	 * Valid operators.
	 *
	 * If you add one here, you must also add its logict to the boolean
	 * expression class.
	 */
	private $operators = array(
		'**',
		'||', '&&',
		'==', '!=', '<=', '>=', '<>', '<', '>',
		'%', '+', '-', '*', '/',
		'.', '!', '^'
	);

	/**
	 * Finds conditionals an returns a token stream for the entire template, with
	 * conditional specific tokens.
	 *
	 * @param $str The template chunk to look through
	 * @return Array [new chunk, new variables]
	 */
	public function tokenize($str)
	{
		if ($str == '')
		{
			return array();
		}

		$this->str = $str;
		$this->tokens = array();

		$this->pushState('OK');

		while ($this->str != '')
		{
			// go to the next LD
			$buffer = $this->seekTo('{');

			// anything we hit in the meantime is template string
			$this->addToken('TEMPLATE_STRING', $buffer);

			// if we can create an {if or {if:elseif token from this point,
			// then we need to move into the statement.
			if ($this->tokenizeIfTags())
			{
				$this->tokenizeIFStatement();
			}
		}

		$this->addToken('TEMPLATE_STRING', $this->str);
		$this->addToken('EOS', TRUE);
		return $this->tokens;
	}

	/**
	 * Finds tokens specific to conditional boolean statements.
	 *
	 * @param $str The template chunk to look through
	 * @return Array [new chunk, new variables]
	 */
	private function tokenizeIfTags()
	{
		// if we hit a closing if, we need to deal with that
		if ($this->peek(5) == '{/if}')
		{
			$this->addToken('ENDIF', $this->move(5));
			return FALSE;
		}

		// potential opening ifs
		$potential_if = (string) $this->peekRegex('{if(:else(\s?}|if\s)|\s)');
		$trimmed_if = trim($potential_if);

		$parts = array(
			'{if'			=> 'IF',
			'{if:elseif'	=> 'ELSEIF',
			'{if:else}'		=> 'ELSE'
		);

		if (isset($parts[$trimmed_if]))
		{
			$token = $parts[$trimmed_if];

			$this->addToken(
				$token,
				$this->move(strlen($potential_if))
			);

			return ($token !== 'ELSE');
		}

		// {if: is a reserved prefix
		if ($this->peek(4) == '{if:')
		{
			throw new ConditionalLexerException('Conditional is invalid: "{if:" is reserverd for conditionals. Found: ' . $potential_if, 20);
		}

		$this->addToken('TEMPLATE_STRING', $this->next());
		return FALSE;
	}

	/**
	 * Finds tokens specific to conditional boolean statements.
	 */
	private function tokenizeIfStatement()
	{
		// No sense continuing if we cannot find a {/if}
		if (strpos($this->str, '{/if}') === FALSE)
		{
			throw new ConditionalLexerException('Conditional is invalid: missing a "{/if}".', 21);
		}

		$last_count = 0;

		while ($this->str != '')
		{
			$this->whitespace();

			if ($this->variable() || $this->number())
			{
				$this->whitespace();
			}

			$this->operators();

			$char = $this->peek();

			if ($char == '"' || $char == "'")
			{
				$this->string();
			}
			elseif ($char == '(' || $char == ')')
			{
				$this->parenthesis();
			}
			elseif ($char == '{' || $char == '}')  // Checking for balanced curly braces
			{
				$this->tag();

				if ($this->topState() == 'END')
				{
					break;
				}
			}

			$new_count = count($this->tokens);

			// when we don't know what to do with a character, we skip it
			// todo: save as misc?
			if ($last_count == $new_count && $this->topState() != 'TAG')
			{
				throw new ConditionalLexerException('Unexpected character: '.$char);
				$this->next();
			}

			$last_count = $new_count;
		}

		// Not in an end state, or curly braces are unbalanced, "error" out
		if ($this->topState() != 'END')
		{
			throw new ConditionalLexerException('Conditional is invalid: not in an end state or unbalanced curly braces.');
		}

		$this->addToken('ENDCOND', '}');
	}

	/**
	 * Try to create a variable token at the current offset
	 */
	public function variable()
	{
		$result = $this->peekRegex($this->patterns['variable']);

		if (isset($result))
		{
			$this->move(strlen($result));
			$this->addToken('VARIABLE', $result);
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Try to create a number token at the current offset
	 */
	public function number()
	{
		$result = $this->peekRegex($this->patterns['number']);

		if (isset($result))
		{
			$this->move(strlen($result));
			$this->addToken('NUMBER', $result);
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Try to create a whitespace token at the current offset
	 */
	public function whitespace()
	{
		if ($ws = $this->peekRegex('\s+'))
		{
			$this->move(strlen($ws));
			$this->addToken('WHITESPACE', $ws);
		}
	}

	/**
	 * Build and add a string token
	 */
	public function string()
	{
		$open_quote = $this->next();

		$str = '';
		$backslash = '\\';
		$escapable = array('\\', "'", '"');

		// Add everything up to the next backslash or closing quote
		// and then check if we're done or just escaping.
		while (TRUE)
		{
			$add = $this->seekTo($open_quote.$backslash);

			if ($add === FALSE || $add === 0) // allows ''
			{
				break;
			}

			$str .= $add;

			if ($open_quote == $this->next())
			{
				break;
			}

			$next = $this->next();

			if ( ! in_array($next, $escapable))
			{
				$str .= $backslash;
			}

			$str .= $next;
		}

		// if we're in a tag we need to keep the quotes
		if ($this->topState() == 'TAG')
		{
			$str = $open_quote.$str.$open_quote;
		}

		$this->addToken('STRING', $str);
	}

	/**
	 * Try to create a parenthesis token at the current offset
	 */
	public function parenthesis()
	{
		$char = $this->peek();

		if ($char == '(')
		{
			$this->addToken('LP', '(');
			$this->next();
		}
		elseif ($char == ')')
		{
			$this->addToken('RP', ')');
			$this->next();
		}
	}

	/**
	 * Try to create a tag token at the current offset
	 */
	public function tag()
	{
		$char = $this->peek();

		if ($char == '{')
		{
			$this->tag_buffer .= '{';

			$this->pushState('TAG');
			$this->next();
		}
		elseif ($char == '}')
		{
			$this->next();
			$this->popState();
			$top = $this->topState();

			if ($top === FALSE)
			{
				$this->pushState('END');
			}
			elseif ($top == 'OK')
			{
				$this->addToken('TAG', $this->tag_buffer.$char);
				$this->tag_buffer = '';
			}
		}
	}

	/**
	 * Add token to the token stream
	 *
	 * @param string $type The type of token being added
	 * @param string $$value The value of the token being added
	 */
	public function addToken($type, $value)
	{
		if ($this->topState() == 'TAG')
		{
			$this->tag_buffer .= $value;
			return;
		}

		// Special cases for Variables
		if ($type == 'VARIABLE')
		{
			$uppercase_value = strtoupper($value);

			switch ($uppercase_value)
			{
				case 'TRUE':
				case 'FALSE':
					$type = 'BOOL';
					break;
				case 'XOR':
				case 'AND':
				case 'OR':
					$type = 'OPERATOR';
					break;
			}
		}

		// Always store strings, even empty ones
		if ($value != '' || $type == 'STRING')
		{
			$this->tokens[] = array($type, $value);
		}
	}

	/**
	 * Try to create an operator token at the current offset
	 */
	private function operators()
	{
		// Consume until we stop seeing operators
		$operator_length = strspn($this->str, implode('', $this->operators));

		if ($operator_length == 0)
		{
			return FALSE;
		}

		$operator_buffer = $this->move($operator_length);

		$last_char = substr($operator_buffer, -1);

		// Handle some edge cases where the next character is a digit
		if (ctype_digit($this->peek()))
		{
			// 1.2 is a number, not two concatenated numbers. To be consistent
			// with that, 1.2.3 should turn into number (1.2), number (.3). So
			// any concatenation with a trailing number is not a valid operation
			// unless there's whitespace. This is also how PHP's token_get_all()
			// handles it.
			// In a similar vein, a '-' at the end of the operator is most likely
			// meant to indicate negativity. Unless its on its own, then it's
			// subtraction, of course.
			if (($last_char == '.') || ($operator_length > 1 && $last_char == '-'))
			{
				$this->str = substr($operator_buffer, -1).$this->str; // Put it back.
				$operator_buffer = substr($operator_buffer, 0, -1);
			}
		}

		if ($operator_buffer == '')
		{
			return FALSE;
		}

		if (in_array($operator_buffer, $this->operators))
		{
			$this->addToken('OPERATOR', $operator_buffer);
		}
		else
		{
			$this->addToken('MISC', $operator_buffer);
		}

		return TRUE;
	}

	/**
	 * Push a state onto the stack
	 *
	 * We use this to keep track of when we're in a tag state.
	 *
	 * @param string $state Name of the state to push
	 */
	private function pushState($state)
	{
		$this->stack[] = $state;
	}

	/**
	 * Pop a state off the stack
	 *
	 * @return The popped state
	 */
	private function popState()
	{
		return array_pop($this->stack);
	}

	/**
	 * Get the top state.
	 *
	 * We use this to keep track of when we're in a tag state.
	 *
	 * @param string $state Name of the state to push
	 */
	private function topState()
	{
		return end($this->stack);
	}
}

/* End of file ConditionalLexer.php */
/* Location: ./system/expressionengine/libraries/template/ConditionalLexer.php */