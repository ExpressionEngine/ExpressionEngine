<?php

namespace EllisLab\ExpressionEngine\Library\Parser\Conditional;

use EllisLab\ExpressionEngine\Library\Parser\AbstractLexer;
use EllisLab\ExpressionEngine\Library\Parser\Conditional\Exception\LexerException;

use EllisLab\ExpressionEngine\Library\Parser\Conditional\Token\Token;
use EllisLab\ExpressionEngine\Library\Parser\Conditional\Token\Boolean;
use EllisLab\ExpressionEngine\Library\Parser\Conditional\Token\Comment;
use EllisLab\ExpressionEngine\Library\Parser\Conditional\Token\Number;
use EllisLab\ExpressionEngine\Library\Parser\Conditional\Token\Operator;
use EllisLab\ExpressionEngine\Library\Parser\Conditional\Token\Other;
use EllisLab\ExpressionEngine\Library\Parser\Conditional\Token\StringLiteral;
use EllisLab\ExpressionEngine\Library\Parser\Conditional\Token\Tag;
use EllisLab\ExpressionEngine\Library\Parser\Conditional\Token\Variable;

use EllisLab\ExpressionEngine\Library\Template\Annotation\Runtime as RuntimeAnnotations;


/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
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
 * @link		https://ellislab.com
 */
class Lexer extends AbstractLexer {

	/**
	 * Available tokens:
	 *
	 * private $token_types = array(
	 * 	'TEMPLATE_STRING',	// generic
	 *  'LD'				// {
	 *  'RD'				// }
	 * 	'IF',				// if
	 * 	'ELSE',				// if:else
	 * 	'ELSEIF',			// if:elseif
	 * 	'ENDIF',			// /if
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

	/**
	 * Tag contents
	 */
	private $tag_buffer = '';

	/**
	 * Tag depth
	 */
	private $tag_depth = 0;

	const COMMENT_PATTERN = "
		\{!--		# open tag
		(.*?)		# anything inbetween
		--\}		# closing tag
	";

	/**
	 * Regex for boolean values
	 */
	const BOOL_PATTERN = "
		\b					# must be its own word
		(true|false)		# The pattern is case insensitive
		(?!(-+)?\w)			# simulate \b with -
	";

	/**
	 * Regex for variables
	 */
	const VARIABLE_PATTERN = "
		\w*(								# word characters on both ends are ok
			[a-zA-Z]([\w:-]+\w)?			# we need at least one alpha in there
			|								# to avoid things like 5-5, and it can't
			(\w[\w:-]+)?[a-zA-Z]			# begin or end in : or -
		)\w*
	";

	/**
	 * Regex for numbers
	 */
	const NUMBER_PATTERN = "
		(
			[0-9]*\.[0-9]+					# You must have a number either
			|								# before or after the dot. The other
			[0-9]+\.[0-9]*					# side is then optional: .5, 5., 1.2
			|
			[0-9]+							# Integers are cool, too
		)
	";

	/**
	 * Pattern used for all of the above patterns. Run as one
	 * to improve performance.
	 */
	private $compiled_pattern;

	/**
	 * Pattern used to match operators. Automatically generated
	 * from the operators array below.
	 */
	private $operator_pattern;

	/**
	 * Valid operators.
	 *
	 * If you add one here, you must also add its logic to the boolean
	 * expression class. If an operator is the same as the beginning of
	 * another, the longer must be first. (e.g. ^= before ^).
	 */
	private $operators = array(
		'^=', '*=', '$=', '~',
		'==', '!=', '<=', '>=', '<>', '<', '>',
		'**', '%', '+', '-', '*', '/',
		'.', '!', '^',
		'||', '&&',
		'AND', 'OR', 'XOR'
	);

	protected $lineno_stack = array();
	protected $context_stack = array();

	protected $lineno;
	protected $context;
	protected $annotations;

	public function __construct()
	{
		$this->lineno = 1;
		$this->context = '';

		$this->operator_pattern = $this->compileOperatorPattern();
		$this->compiled_pattern = $this->compilePattern();

		$this->annotations = new RuntimeAnnotations();
		$this->annotations->useSharedStore();
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
		$this->str = $str;
		$this->tokens = array();

		while ( ! $this->eof())
		{
			// go to the next LD
			$buffer = $this->seekTo('{');

			// anything we hit in the meantime is template string
			$this->addToken('TEMPLATE_STRING', $buffer);

			// check for template tags
			$this->templateTags();
		}

		if ($this->tag_depth !== 0)
		{
			throw new LexerException('Unclosed tag.');
		}

		$this->addToken('TEMPLATE_STRING', $this->rest());
		$this->addToken('EOS', TRUE);

		unset($this->str);

		return $this->tokens;
	}

	/**
	 * We saw a {, check if it's an ee tag that we can use.
	 */
	private function templateTags()
	{
		if ($this->peek(5) == '{/if}')
		{
			$this->addToken('LD', '{');
			$this->addToken('ENDIF', '/if');
			$this->move(4);
		}
		elseif ($this->peek(9) == '{if:else}')
		{
			$this->addToken('LD', '{');
			$this->addToken('ELSE', 'if:else');
			$this->move(8);
		}
		elseif ($if = $this->peekRegex('\{(if(:elseif)?\s)'))
		{
			$last = end($this->tokens);

			if ( ! $last || $last->type != 'COMMENT' || ! $last->conditional_annotation)
			{
				$annotation_token = new Comment(
					$this->annotations->create(array(
						'context' => $this->context,
						'lineno' => $this->lineno,
						'conditional' => TRUE
					))
				);

				// mark for the parser to remove when the conditional
				// is resolved
				$annotation_token->conditional_annotation = TRUE;

				$this->tokens[] = $annotation_token;
			}

			$this->addToken('LD', '{');

			if (strlen($if) == 4)
			{
				$this->move(3);
				$this->addToken('IF', 'if');
			}
			else
			{
				$this->move(10);
				$this->addToken('ELSEIF', 'if:elseif');
			}

			$this->whitespace();
			$this->expression();
		}
		elseif ($comment = $this->peekRegex(self::COMMENT_PATTERN, 'usx'))
		{
			$this->addToken('COMMENT', $this->move(strlen($comment)));
			return;
		}
		else
		{
			$this->addToken('TEMPLATE_STRING', $this->next());

			// future: $this->tag();

			if ($this->peek(3) == 'if:')
			{
				throw new LexerException('if: is a reserved prefix.');
			}

			return;
		}

		$this->whitespace();

		if ($this->peek() == '}')
		{
			$this->next();
			$this->addToken('RD', '}');
		}
	}

	/**
	 * Finds tokens specific to conditional boolean statements.
	 */
	private function expression()
	{
		while ( ! $this->eof())
		{
			$this->whitespace();

			$char = $this->peek();

			if ($char == '}' && $this->tag_depth == 0)  // Checking for balanced curly braces
			{
				return;
			}
			elseif ($char == '(' || $char == ')')
			{
				$this->parenthesis();
			}
			elseif ($this->operator())
			{
				; // nothing
			}
			elseif ($this->value())
			{
				; // nothing
			}
			elseif ($char == '{')
			{
				$this->next();
				$this->tag();
			}
			else
			{
				$this->next();
				$this->addToken('MISC', $char);
			}
		}
	}

	/**
	 * We've entered a tag, find the end while respecting proper quoting.
	 */
	private function tag()
	{
		$this->tag_depth++;
		$this->tag_buffer .= '{';

		while (($char = $this->peek()) !== FALSE)
		{
			switch ($char)
			{
				case '}':
					$this->next();
					break 2;
				case '"':
				case "'":
					$this->string();
					break;
				case '{':
					$this->next();
					$this->tag();
					break;
				default:
					$this->tag_buffer .= $this->next();
			}
		}

		$this->tag_buffer .= '}';
		$this->tag_depth--;

		if ($this->tag_depth == 0)
		{
			$this->addToken('TAG', $this->tag_buffer);
			$this->tag_buffer = '';
		}
	}

	/**
	 * Try to create a whitespace token at the current offset
	 */
	private function whitespace()
	{
		if ($ws = $this->peekRegex('\s+'))
		{
			$this->move(strlen($ws));
			$this->addToken('WHITESPACE', $ws);
		}
	}

	/**
	 * Variables and Scalars
	 */
	private function value()
	{
		if (preg_match($this->compiled_pattern, $this->str, $matches))
		{
			foreach (array_reverse($matches) as $type => $value)
			{
				if (is_string($type))
				{
					$this->addToken($type, $value);
					$this->move(strlen($value));
					return TRUE;
				}
			}
		}

		$char = $this->peek();

		if ($char == '"' || $char == "'")
		{
			$this->string();
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Operators
	 */
	private function operator()
	{
		$operator = $this->peekRegex($this->operator_pattern, 'usi');

		if (isset($operator))
		{
			$this->move(strlen($operator));
			$this->addToken('OPERATOR', $operator);
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Build and add a string token
	 */
	private function string()
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

			if ($this->eof())
			{
				throw new LexerException('Unclosed string.');
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
		if ($this->tag_depth > 0)
		{
			$this->tag_buffer .= $open_quote.$str.$open_quote;
		}
		else
		{
			$this->addToken('STRING', $str);
		}
	}

	/**
	 * Try to create a parenthesis token at the current offset
	 */
	private function parenthesis()
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
	 * Create the pattern that matches operators.
	 */
	private function compileOperatorPattern()
	{
		$pattern = '';

		foreach ($this->operators as $operator)
		{
			$operator = preg_quote($operator, '/');

			// Special negative lookahead addition for concatenation
			// 1.2 is a number, not two concatenated numbers. To be consistent
			// with that, 1.2.3 should turn into number (1.2), number (.3). So
			// any concatenation with a trailing number is not a valid operation
			// unless there's whitespace. This is also how PHP's token_get_all()
			// handles it.
			if ($operator == '\.')
			{
				$operator = $operator.'(?!\d)';
			}
			elseif (ctype_alpha($operator[0]))
			{
				$operator = '\b'.$operator.'(?!(-+)?\w)';
			}

			$pattern .= $operator.'|';
		}

		return $pattern = substr($pattern, 0, -1);
	}

	/**
	 * Compile the regular expressions into one big
	 * matching pattern.
	 */
	private function compilePattern()
	{
		return '/('.
			'(?P<COMMENT>'.self::COMMENT_PATTERN.')|'.
			'(?P<BOOL>'.self::BOOL_PATTERN.')|'.
			'(?P<VARIABLE>'.self::VARIABLE_PATTERN.')|'.
			'(?P<NUMBER>'.self::NUMBER_PATTERN.')'.
			')/Aiusx';
	}

	/**
	 * Add token to the token stream
	 *
	 * @param string $type	 The type of token being added
	 * @param string $lexeme The value of the token being added
	 */
	private function addToken($type, $lexeme)
	{
		// Always store strings, even empty ones
		if ($lexeme != '' || $type == 'STRING')
		{
			$this->lineno += substr_count($lexeme, "\n");

			// check comments for annotations
			if ($type == 'COMMENT')
			{
				if ($annotation = $this->annotations->read($lexeme))
				{
					$this->syncWithAnnotation($annotation);
				}
			}

			switch ($type)
			{
				case 'BOOL':	 $obj = new Boolean($lexeme);
					break;
				case 'COMMENT':	 $obj = new Comment($lexeme);
					break;
				case 'NUMBER':	 $obj = new Number($lexeme);
					break;
				case 'OPERATOR': $obj = new Operator($lexeme);
					break;
				case 'OTHER':	 $obj = new Other($lexeme);
					break;
				case 'STRING':	 $obj = new StringLiteral($lexeme);
					break;
				case 'TAG':		 $obj = new Tag($lexeme);
					break;
				case 'VARIABLE': $obj = new Variable($lexeme);
					break;
				default:
					$obj = new Token($type, $lexeme);
			}

			// (Re-)Mark conditional annotation comments so the parser
			// knows that it can remove them after dealing with the
			// conditional.
			if (isset($annotation) && isset($annotation->conditional))
			{
				$obj->conditional_annotation = TRUE;
			}

			$obj->lineno = $this->lineno;
			$obj->context = $this->context;

			$this->tokens[] = $obj;
		}
	}

	/**
	 * Sync the lexer line number state and context with
	 * a given annotation.
	 *
	 * @param Object $annotation The annotation to sync with
	 */
	private function syncWithAnnotation($annotation)
	{
		if (isset($annotation->context))
		{
			if ($annotation->context == $this->context)
			{
				// just a line number marker
				if (isset($annotation->lineno))
				{
					$this->lineno = $annotation->lineno;
				}
			}
			elseif ($annotation->context == end($this->context_stack))
			{
				// returning to a previous context
				$this->context = array_pop($this->context_stack);
				$this->lineno = array_pop($this->lineno_stack);
			}
			else
			{
				// entering a new context
				$this->context_stack[] = $this->context;
				$this->lineno_stack[] = $this->lineno;

				$this->lineno = 1;
				$this->context = $annotation->context;
			}
		}


		// If the annotation did not yet have a linenumber, then this is
		// the best guess we have, so we assign it here.
		if ( ! isset($annotation->lineno))
		{
			$annotation->lineno = $this->lineno;
		}
	}
}

// EOF
