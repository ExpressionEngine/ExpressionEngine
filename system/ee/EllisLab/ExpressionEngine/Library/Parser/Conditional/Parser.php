<?php

namespace EllisLab\ExpressionEngine\Library\Parser\Conditional;

use EllisLab\ExpressionEngine\Library\Parser\AbstractParser;
use EllisLab\ExpressionEngine\Library\Parser\Conditional\Exception\ParserException;
use EllisLab\ExpressionEngine\Library\Parser\Conditional\Exception\BooleanExpressionException;
use EllisLab\ExpressionEngine\Library\Parser\Conditional\Token\Boolean;

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
 * @link		https://ellislab.com
 */
class Parser extends AbstractParser {

	protected $output = '';
	protected $output_buffers = array();

	protected $variables = array();

	protected $safety = FALSE;

	protected $last_conditional_annotation;

	private $ignore_whitespace = FALSE;

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

		$out = $this->closeBuffer(FALSE);

		return preg_replace('/^\n?(.*?)\n?$/is', '$1', $out);
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
	 * Output the last conditional annotation.
	 *
	 * Do *NOT* call this unless you know why. It's used by the conditional
	 * statement class to re-insert conditional annotations when it has to
	 * write a conditional back out.
	 */
	public function outputLastAnnotation()
	{
		if ( ! isset($this->last_conditional_annotation))
		{
			return;
		}

		$this->output($this->last_conditional_annotation->lexeme);
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
				$token = $this->token;

				$conditional = new Statement($this);

				try
				{
					$this->conditional($conditional);
				}
				catch (BooleanExpressionException $e)
				{
					throw new ParserException(
						$this->getRethrowMessage($e, $token)
					);
				}

				$this->expectTag('ENDIF', $token);
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
			$this->next();
			$this->template();
		}
		else
		{
			$this->next(FALSE);
			$this->skipConditionalBody();
		}

		while ($this->acceptTag('ELSEIF'))
		{
			$elseif_expression = $this->condition();

			if ($conditional->addElseIf($elseif_expression))
			{
				$this->next();
				$this->template();
			}
			else
			{
				$this->next(FALSE);
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
		$this->ignore_whitespace = TRUE;

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
			elseif ($this->is('TEMPLATE_STRING') || $this->is('COMMENT'))
			{
				$this->next(FALSE);
				continue;
			}
			elseif ($conditional_depth == 0)
			{
				break;
			}

			$this->next(FALSE);
		}
		while ($this->valid());

		$this->ignore_whitespace = FALSE;
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

		// Expect an RD, but don't move to the next token
		// as it may be a comment annotating the next conditional
		// when we're not actually there yet
		// e.g. {if current}{!-- don't touch this yet --}{if nested}...
		if ( ! $this->is('RD'))
		{
			throw new ParserException(
				$this->expectedMessage('RD')
			);
		}

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
				if ( ! $this->token->canEvaluate())
				{
					$this->addSafely($expression);
				}
				else
				{
					$expression->add($this->token);
				}

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

			// can't do objects
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
			$expression->add(new Boolean(FALSE));
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
		$expression->add(new Boolean(FALSE));
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
	protected function next($skip_and_output_comments = TRUE)
	{
		parent::next();

		if ($this->is('WHITESPACE'))
		{
			$this->whitespace();
			$this->next();
		}

		if ($this->is('COMMENT'))
		{
			if ($this->token->conditional_annotation)
			{
				$this->last_conditional_annotation = $this->token;

				if ($skip_and_output_comments)
				{
					return $this->next();
				}
			}

			if ($skip_and_output_comments)
			{
				$this->output($this->value());
				$this->next();
			}
		}
	}

	/**
	 * Add whitespace
	 */
	protected function whitespace()
	{
		if ( ! $this->ignore_whitespace && substr($this->output, -1) != ' ')
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
	protected function closeBuffer($trim = TRUE)
	{
		$out = array_pop($this->output_buffers);
		$this->initBuffer();
		return $trim ? trim($out) : $out;
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
	 * @param String $expect_type The type to check against
	 * @return Bool  Expected token was found
	 * @throws ParserException If expected token is not found
	 */
	protected function expect($expect_type, $open = NULL)
	{
		if (parent::expect($expect_type) === FALSE)
		{
			throw new ParserException(
				$this->expectedMessage($expect_type, $open)
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
	protected function expectTag($expect_type, $open = NULL)
	{
		if ( ! $this->acceptTag($expect_type))
		{
			throw new ParserException(
				$this->expectedMessage($expect_type.' tag', $open)
			);
		}

		return TRUE;
	}

	/**
	 * Rethrow an error message.
	 *
	 * Since a rethrow can happen after tokens have been consumed,
	 * we require that a token is given for state information.
	 */
	private function getRethrowMessage($exception, $token = NULL)
	{
		$message = $exception->getMessage();

		$location	= $token->context;
		$lineno		= $token->lineno;

		return $message . "\n\nIn $location on line $lineno";
	}

	/**
	 * Construct an error message for when we find ourselves in a state
	 * we can't resolve.
	 *
	 * We try to be as verbose and intelligent as possible. This is not
	 * hit during regular execution, so it can be pretty heavy.
	 *
	 * @param String $expected Expected token type
	 * @param Token $open The token that last opened a tag
	 */
	private function expectedMessage($expected, $open = NULL)
	{
		$value		= $this->value();
		$found_type	= $this->token->type;
		$location	= $this->token->context;
		$lineno		= $this->token->lineno;

		if ($found_type != 'VARIABLE' && strlen($value) > 23)
		{
			$value = substr($value, 0, 20).'...';
		}

		if ($found_type == 'EOS')
		{
			$found_description = "end of $location on line $lineno";
			$expected_description = "$expected";

			if ($open)
			{
				$expected_description .= " for opening on line ".$open->lineno;

				if ($open->context != $location)
				{
					$expected_description .= ' in '.$open->context;
				}
			}
		}
		else
		{
			$found_description = "'$value' ($found_type)";
			$expected_description = "$expected in $location on line $lineno";
		}

		$message = "Unexpected $found_description; expected $expected_description.";

		return $message;
	}
}

// EOF
