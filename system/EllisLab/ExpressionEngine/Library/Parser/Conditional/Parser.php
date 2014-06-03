<?php

namespace EllisLab\ExpressionEngine\Library\Parser\Conditional;

use EllisLab\ExpressionEngine\Library\Parser\AbstractParser;
use EllisLab\ExpressionEngine\Library\Parser\Conditional\Exception\ParserException;
use EllisLab\ExpressionEngine\Library\Parser\Conditional\Token\Bool;
use EllisLab\ExpressionEngine\Library\Template\Annotation\Runtime as RuntimeAnnotations;

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
 * ExpressionEngine Core Conditional Parser Class
 *
 * Implemented as a recursive descent parser.
 *
 * The Grammar, written without left recursion for clarity.
 *
 *  template = [TEMPLATE_STRING | conditional]*
 *  conditional = LD IF expr RD template (ELSEIF expr RD template)* (ELSE template)? ENDIF
 *  expr = bool_expr | value
 *  bool_expr = value OPERATOR expr | parenthetical_expr OPERATOR expr
 *  parenthetical_expr = LP expr RP
 *  value = NUMBER | STRING | BOOL | VARIABLE | TAG
 *
 * The grammar as it stands should be LL(1) and LALR compatible. If anyone
 * goes really code happy, be my guest.
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Parser extends AbstractParser {

	protected $output = '';
	protected $output_buffers = array();

	protected $variables = array();

	protected $safety = FALSE;

	protected $context;
	protected $annotations;

	public function __construct($tokens)
	{
		parent::__construct($tokens);

		$this->annotations = new RuntimeAnnotations();
		$this->annotations->useSharedStore();
	}

	public function parse()
	{
		$this->openBuffer();

		do
		{
			$this->next();
			$this->template();
		}
		while (count($this->tokens));

		$this->expect('EOS');

		return $this->closeBuffer();
	}

	/**
	 * Set the variables to use for the parsing
	 */
	public function setVariables($vars)
	{
		$this->variables = $vars;
	}

	/**
	 * Turn safety on.
	 *
	 * When safety is on, any non-scalars are turned into FALSE so that
	 * the conditional can fully execute
	 */
	public function safetyOn()
	{
		$this->safety = TRUE;
	}

	/**
	 * Template production rule
	 */
	protected function template()
	{
		// this loop is identical to calling $this->template() at the
		// end of both if branches, but avoids the added weight on the stack
		while (TRUE)
		{
			if ($this->is('TEMPLATE_STRING'))
			{
				$this->output($this->value());
				$this->next();
			}
			elseif ($this->acceptTag('IF'))
			{
					$conditional = new Statement($this);
					$this->conditional($conditional);

					$this->expectTag('ENDIF');
					$this->expect('RD');

					$conditional->closeIf();
			}
			else
			{
				break;
			}
		}
	}

	/**
	 * Conditional production rule
	 *
	 * {if condition}
	 *     template
	 * {if:else condition}
	 *     template
	 * {if:else}
	 *     template
	 * {/if}
	 */
	protected function conditional($conditional)
	{
		$if_expression = $this->condition();

		if ($conditional->addIf($if_expression))
		{
			$this->template();
		}
		else
		{
			$this->skipConditionalBody();
		}

		while ($this->acceptTag('ELSEIF'))
		{
			$elseif_expression = $this->condition();

			if ($conditional->addElseIf($elseif_expression))
			{
				$this->template();
			}
			else
			{
				$this->skipConditionalBody();
			}
		}

		if ($this->acceptTag('ELSE'))
		{
			$this->expect('RD');

			if ($conditional->addElse())
			{
				$this->template();
			}
			else
			{
				$this->skipConditionalBody();
			}
		}
	}

	/**
	 * Seek past the body of the conditional. This method
	 * will also skip any nested conditionals since we don't
	 * need to evaluate those if they are not going to be output.
	 */
	protected function skipConditionalBody()
	{
		$conditional_depth = 0;

		do
		{
			if ($this->isTag('ENDIF'))
			{
				if ($conditional_depth == 0)
				{
					break;
				}

				$conditional_depth--;

				// skip the LD and ENDIF and expect an RD
				// to form {/if}.
				$this->next();
				$this->next();
				$this->expect('RD');
				continue;
			}
			elseif ($this->isTag('IF'))
			{
				$conditional_depth++;
				$this->next();
			}
			elseif ( ! $this->is('TEMPLATE_STRING') && $conditional_depth == 0)
			{
				break;
			}

			$this->next();
		}
		while ($this->valid());
	}

	/**
	 * The condition and closing brace.
	 *
	 * 5 == 7 && bob - mary}
	 */
	protected function condition()
	{
		$this->openBuffer();

		$expression = $this->expression();
		$this->expect('RD');

		$this->closeBuffer(); // discard whitespace added by next()

		return $expression;
	}

	/**
	 * Boolean Expressions
	 *
	 * This does the left side of the expression and then loops if that ends
	 * in an operator.
	 */
	protected function expression()
	{
		$expression = new BooleanExpression();

		do
		{
			$continue_loop = FALSE;

			while ($this->is('LP'))
			{
				$expression->add($this->token);
				$this->next();
			}

			if ($this->is('BOOL') ||
				$this->is('NUMBER') ||
				$this->is('STRING'))
			{
				$expression->add($this->token);
				$this->next();
			}
			elseif ($this->is('VARIABLE'))
			{
				$this->variable($expression);
				$this->next();
			}
			elseif ($this->is('MISC'))
			{
				$this->addSafely($expression);
				$this->next();
			}

			// A closing parenthesis would be before the operator
			while ($this->is('RP'))
			{
				$expression->add($this->token);
				$this->next();
			}

			// If we hit an operator, we need to go around again
			// looking for the right hand value.
			if ($this->is('OPERATOR'))
			{
				$expression->add($this->token);
				$this->next();

				$continue_loop = TRUE;
			}

			// Seek past tags, adding them to output, but pretending that
			// they are basically not there. This allows for arbitrary tag
			// embedding in places where we would otherwise consider variables
			// illegal. This is fine because we don't know what those tags will
			// evaluate to.
			while ($this->is('TAG'))
			{
				$this->addSafely($expression);
				$this->next();
				$continue_loop = TRUE;
			}
		}
		while ($continue_loop == TRUE);

		return $expression;
	}

	/**
	 * Variable Values
	 */
	protected function variable($expression)
	{
		$name = $this->value();

		if (array_key_exists($name, $this->variables))
		{
			$value = $this->variables[$name];

			// can't do arrays
			if (is_array($value))
			{
				return $this->addFalse($expression);
			}

			// can't do
			if (is_object($value) AND ! method_exists($value, '__toString'))
			{
				return $this->addFalse($expression);
			}

			$this->token->setValue($value);
		}
		elseif ($this->safety === TRUE)
		{
			return $this->addFalse($expression);
		}

		$expression->add($this->token);
	}

	/**
	 * Embedded Tags and Miscellaneous Junk
	 */
	protected function addSafely($expression)
	{
		if ($this->safety === TRUE)
		{
			$expression->add(new Bool(FALSE));
		}
		else
		{

			$expression->add($this->token);
		}
	}

	/**
	 * Add a false token
	 *
	 * These are used to replace invalid values.
	 */
	protected function addFalse($expression)
	{
		$expression->add(new Bool(FALSE));
	}

	/**
	 * Add to the current output buffer
	 */
	public function output($value)
	{
		$this->output .= $value;
	}

	/**
	 * Move to the next token
	 */
	protected function next()
	{
		parent::next();

		if ($this->is('WHITESPACE'))
		{
			$this->whitespace();
			$this->next();
		}

		if ($this->is('COMMENT'))
		{
			if ($annotation = $this->annotations->read($this->value()))
			{
				if ($annotation->context)
				{
					$this->context = $annotation->context;
				}
			}

			$this->next();
		}
	}

	/**
	 * Add whitespace
	 */
	protected function whitespace()
	{
		if (substr($this->output, -1) != ' ')
		{
			$this->output(' ');
		}
	}

	/**
	 * Open a new output buffer
	 */
	protected function openBuffer()
	{
		$this->output_buffers[] = '';
		$this->initBuffer();
	}

	/**
	 * Close and flush the current output buffer
	 */
	protected function closeBuffer()
	{
		$out = array_pop($this->output_buffers);
		$this->initBuffer();
		return trim($out);
	}

	/**
	 * Initialize the buffer pointer so we can append
	 * to `$this->output` without worrying about which
	 * buffer to use.
	 */
	protected function initBuffer()
	{
		$this->output =& $this->output_buffers[count($this->output_buffers) - 1];
	}

	/**
	 * Check if there is a tag at the current offset
	 *
	 * Works like is() but enforces an LD and then compares
	 * to the next token.
	 *
	 * @param String $type The type to check against
	 * @return Bool  Current token is LD and next is type
	 */
	protected function isTag($type)
	{
		if ($this->is('LD'))
		{
			$next = current($this->tokens);

			if ($next->type == $type)
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * Enforce an expected token.
	 *
	 * Overrides the abstract one to throw an exception.
	 *
	 * @param String $type The type to check against
	 * @return Bool  Expected token was found
	 * @throws ParserException If expected token is not found
	 */
	protected function expect($type)
	{
		if (parent::expect($type) === FALSE)
		{
			$location = "\n\nIn ".$this->context;

			throw new ParserException(
				'Unexpected ' . $this->token->type . '(' . $this->value() .') '.
				'expected ' . $type . '. ' .
				$location
			);
		}

		return TRUE;
	}

	/**
	 * Accept a tag token.
	 *
	 * Works like accept, but assumes that the token is preceded by an LD.
	 */
	protected function acceptTag($type)
	{
		if ( ! $this->isTag($type))
		{
			return FALSE;
		}

		$this->openBuffer();
		$this->next();
		$this->next();
		$this->closeBuffer(); // discard next()'s whitespace
		return TRUE;
	}

	/**
	 * Expect a tag token
	 *
	 * Works like expect, but assumes that the token is preceded by an LD.
	 */
	protected function expectTag($type)
	{
		if ( ! $this->acceptTag($type))
		{
			$location = "\n\nIn ".$this->context;

			throw new ParserException(
				'Unexpected ' . $this->token->type . '(' . $this->value() .') '.
				'expected ' . $type . ' tag. ' .
				$location
			);		}

		return TRUE;
	}
}