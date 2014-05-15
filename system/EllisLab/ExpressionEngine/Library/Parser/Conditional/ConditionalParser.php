<?php

namespace EllisLab\ExpressionEngine\Library\Parser\Conditional;

use EllisLab\ExpressionEngine\Library\Parser\AbstractParser;
use EllisLab\ExpressionEngine\Library\Parser\Conditional\Exception\ConditionalParserException;

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
 *  conditional = IF expr ENDCOND template (ELSEIF expr ENDCOND template)* (ELSE template)? ENDIF
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
class ConditionalParser extends AbstractParser {

	protected $output = '';
	protected $output_buffers = array();

	protected $variables = array();

	protected $safety = FALSE;

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
	 * they can't be used to muck with our eval.
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
			elseif ($this->accept('IF'))
			{
				$conditional = new ConditionalStatement($this);

				$this->conditional($conditional);
				$this->expect('ENDIF');
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

		while ($this->accept('ELSEIF'))
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

		if ($this->accept('ELSE'))
		{
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

		while (TRUE)
		{
			if ($this->is('ENDIF'))
			{
				if ($conditional_depth == 0)
				{
					break;
				}

				$conditional_depth--;
			}
			elseif ($this->is('IF'))
			{
				$conditional_depth++;
			}
			elseif ( ! $this->is('TEMPLATE_STRING') && $conditional_depth == 0)
			{
				break;
			}

			$this->next();
		}
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
		$this->expect('ENDCOND');

		$this->closeBuffer(); // discard whitespace added by next()

		return $expression;
	}

	/**
	 * Boolean Expressions
	 *
	 * This does the left side of the expression and then loops if that ends
	 * in an operator. Parenthetical subexpressions are done recursively.
	 */
	protected function expression()
	{
		$expression = new BooleanExpression();

		do
		{
			$continue_loop = FALSE;

			while ($this->accept('LP'))
			{
				$expression->add('LP', '(');
			}

			if ($this->is('BOOL'))
			{
				$expression->add('BOOL', ($this->value() == 'TRUE'));
				$this->next();
			}
			elseif ($this->is('NUMBER'))
			{
				$expression->add('NUMBER', $this->value());
				$this->next();
			}
			elseif ($this->is('STRING'))
			{
				$this->string($expression);
				$this->next();
			}
			elseif ($this->is('VARIABLE'))
			{
				$this->variable($expression);
				$this->next();
			}
			elseif ($this->is('MISC'))
			{
				$this->misc($expression);
				$this->next();
			}

			// A closing parenthesis would be before the operator
			while ($this->accept('RP'))
			{
				$expression->add('RP', ')');
			}

			// If we hit an operator, we need to go around again
			// looking for the right hand value.
			if ($this->is('OPERATOR'))
			{
				$expression->add('OPERATOR', $this->value());
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
				$this->tag($expression);
				$this->next();
				$continue_loop = TRUE;
			}
		}
		while ($continue_loop == TRUE);

		return $expression;
	}

	/**
	 * String Values
	 */
	protected function string($expression)
	{
		$value = $this->value();

		$value = $this->encodeString($value);
		$can_eval = (stristr($value, LD) === FALSE);

		$expression->add('STRING', $value, $can_eval, TRUE);
	}

	/**
	 * Variable Values
	 */
	protected function variable($expression)
	{
		$quote = FALSE;
		$can_eval = FALSE;

		$value = $name = $this->value();

		if (array_key_exists($name, $this->variables))
		{
			$value = $this->variables[$name];
			$value = $this->encode(
				$this->safeCastToString(
					$this->variables[$name]
				)
			);

			$quote = TRUE;
			$can_eval = TRUE;
		}
		elseif ($this->safety === TRUE)
		{
			$value = FALSE;
			$can_eval = TRUE;
		}

		$expression->add('VARIABLE', $value, $can_eval, $quote);
	}

	/**
	 * Embedded Tags
	 */
	protected function tag($expression)
	{
		if ($this->safety === TRUE)
		{
			$expression->add('BOOL', FALSE);
		}
		else
		{
			$expression->add('TAG', $this->value(), FALSE);
		}
	}

	/*
	 * Miscellaneous Junk
	 */
	protected function misc($expression)
	{
		if ($this->safety === TRUE)
		{
			$expression->add('BOOL', FALSE);
		}
		else
		{
			$expression->add('MISC', $this->value(), FALSE);
		}
	}

	/*
	 * Encode a string literal
	 */
	protected function encodeString($value)
	{
		if (stristr($value, LD.'exp:') && stristr($value, RD) && $this->safety === FALSE)
		{
			// Do not encode embedded tags in strings when safety is FALSE
			return $value;
		}

		// If it has braces we do not want to encode them except
		// when the safety is on (which is enforced in `encode()`).
		return $this->encode($value, FALSE);
	}

	/**
	 * Encode a variable
	 */
	protected function encode($value, $encode_braces = TRUE)
	{
		// TRUE AND FALSE values are for short hand conditionals,
		// like {if logged_in} and so we have no need to remove
		// unwanted characters and we do not quote it.
		if ($value == 'TRUE' || $value == 'FALSE')
		{
			return $value;
		}

		if (strlen($value) > 100)
		{
			$value = substr(htmlspecialchars($value), 0, 100);
		}

		$value = preg_replace('/\s+/', ' ', $value);

		if ($encode_braces || $this->safety === TRUE)
		{
			$value = str_replace(
				array('{', '}',),
				array('&#123;', '&#125;',),
				$value
			);
		}

		return $value;
	}

	/**
	 * Take a user variable and cast it to a string, taking
	 * into account that some developers pass arrays, and
	 * objects.
	 */
	protected function safeCastToString($value)
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

		return (string) $value;
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

		if ($this->token[0] == 'WHITESPACE')
		{
			$this->whitespace();
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
	 * Enforce an expected token.
	 *
	 * Overrides the abstract one to throw an exception.
	 *
	 * @param String $token_name The name to check against
	 * @return Bool  Expected token was found
	 * @throws ConditionalParserException If expected token is not found
	 */
	protected function expect($token_name)
	{
		if (parent::expect($token_name) === FALSE)
		{
			throw new ConditionalParserException('Unexpected ' . $this->token[0] . ' (' . $this->token[1] . ') expected ' . $token_name . '.');
		}

		return TRUE;
	}
}