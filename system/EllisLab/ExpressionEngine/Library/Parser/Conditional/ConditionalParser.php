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
		$this->openBuffer();

		$can_evaluate = $this->condition();

		if ($conditional->addIf($this->closeBuffer(), $can_evaluate))
		{
			$this->template();
		}
		else
		{
			$this->skipConditionalBody();
		}

		while ($this->accept('ELSEIF'))
		{
			$this->openBuffer();
			$can_evaluate = $this->condition();

			if ($conditional->addElseIf($this->closeBuffer(), $can_evaluate))
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
		$can_evaluate = $this->expression();
		$this->expect('ENDCOND');

		return $can_evaluate;
	}

	/**
	 * Boolean Expressions
	 *
	 * This does the left side of the expression and recurses if it finds an
	 * operator, which indicates that there is a right side.
	 */
	protected function expression()
	{
		// An empty conditional can technically evaluate, so by default
		// everything is true.
		$can_evaluate = TRUE;

		if ($this->accept('LP'))
		{
			$this->output('(');

			$can_evaluate = $this->expression();

			$this->output(')');
			$this->expect('RP');
		}
		elseif ($this->is('STRING') || $this->is('NUMBER') || $this->is('BOOL'))
		{
			$prepped = $this->scalar($this->value());

			// If there's a potential tag inside a string, we can't risk
			// evaluating. This will have to wait for safety on.
			if ($this->is('STRING') && stristr($prepped, LD))
			{
				$can_evaluate = FALSE;
			}

			$this->output($prepped);
			$this->next();
		}
		elseif ($this->is('VARIABLE'))
		{
			list($can_eval, $value) = $this->variable($this->value());

			if ($can_eval === FALSE)
			{
				$can_evaluate = FALSE;
			}

			$this->output($value);
			$this->next();
		}
		elseif ($this->is('TAG'))
		{
			$can_evaluate = FALSE;
			$this->output($this->tag($this->value()));
			$this->next();
		}
		elseif ($this->is('MISC'))
		{
			$can_evaluate = FALSE;
			$this->output($this->misc($this->value()));
			$this->next();
		}

		if ($this->is('OPERATOR'))
		{
			$this->whitespace();
			$this->output($this->value());
			$this->whitespace();
			$this->next();
			$sub_expression_can_eval = $this->expression();

			// If we already cannot evaluate, the sub expression result
			// does not matter and might accidental flip us to false.
			if ($can_evaluate === TRUE)
			{
				$can_evaluate = $sub_expression_can_eval;
			}
		}

		if ($this->safety === TRUE)
		{
			return TRUE;
		}

		return $can_evaluate;
	}

	/**
	 * Scalar Values
	 */
	protected function scalar($value)
	{
		if ($this->is('BOOL'))
		{
			return (strtoupper($value) == 'TRUE') ? 'TRUE' : 'FALSE';
		}
		elseif ($this->is('NUMBER'))
		{
			return $value;
		}

		return $this->encodeString($value);
	}

	/**
	 * Variable Values
	 */
	protected function variable($name)
	{
		if (array_key_exists($name, $this->variables))
		{
			$value = $this->variables[$name];
			$value = $this->encode(
				$this->safeCastToString(
					$this->variables[$name]
				)
			);

			return array(TRUE, $value);
		}

		if ($this->safety === TRUE)
		{
			return array(TRUE, 'FALSE');
		}

		return array(FALSE, $name);
	}

	/**
	 * Embedded Tags
	 */
	protected function tag($value)
	{
		if ($this->safety === TRUE)
		{
			return 'FALSE';
		}

		return $value;
	}

	/*
	 * Miscellaneous Junk
	 */
	protected function misc($value)
	{
		if ($this->safety === TRUE)
		{
			return 'FALSE';
		}

		return $value;
	}

	/*
	 * Encode a string literal
	 */
	protected function encodeString($value)
	{
		if (stristr($value, LD.'exp:') && stristr($value, RD) && $this->safety === FALSE)
		{
			// Do not encode embedded tags in strings when safety is FALSE
			return '"' . $value . '"';
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

		$value = str_replace(
			array("'", '"', '(', ')', '$', "\n", "\r", '\\'),
			array('&#39;', '&#34;', '&#40;', '&#41;', '&#36;', '', '', '&#92;'),
			$value
		);

		if ($encode_braces || $this->safety === TRUE)
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