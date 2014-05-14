<?php

/*
	The Grammar, written without left recursion for clarity.

	template = [TEMPLATE_STRING | conditional]*
	conditional = IF expr ENDCOND template (ELSEIF expr ENDCOND template)* (ELSE template)? ENDIF
	expr = bool_expr | value
	bool_expr = value OPERATOR expr | parenthetical_expr OPERATOR expr
	parenthetical_expr = LP expr RP
	value = NUMBER | STRING | BOOL | VARIABLE | TAG

*/

/**
 * Implemented as a recursive descent parser.
 *
 * The grammar as it stands should be LL(1) and LALR compatible. If anyone
 * goes really code happy, be my guest.
 */
class Conditional_parser extends RecursiveDescentParser {

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
				$conditional = new Conditional_statement($this);

				$this->conditional($conditional);
				$this->expect('ENDIF');
				$conditional->end_if();
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

		if ($conditional->add_if($this->closeBuffer(), $can_evaluate))
		{
			$this->template();
		}
		else
		{
			$this->skip_conditional_body();
		}

		while ($this->accept('ELSEIF'))
		{
			$this->openBuffer();
			$can_evaluate = $this->condition();

			if ($conditional->add_elseif($this->closeBuffer(), $can_evaluate))
			{
				$this->template();
			}
			else
			{
				$this->skip_conditional_body();
			}
		}

		if ($this->accept('ELSE'))
		{
			if ($conditional->add_else())
			{
				$this->template();
			}
			else
			{
				$this->skip_conditional_body();
			}
		}
	}

	/**
	 * Seek past the body of the conditional. This method
	 * will also skip any nested conditionals since we don't
	 * need to evaluate those if they are not going to be output.
	 */
	protected function skip_conditional_body()
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
}




abstract class RecursiveDescentParser {

	protected $token;
	protected $tokens;

	public function __construct($tokens)
	{
		$this->tokens = $tokens;
	}

	abstract public function parse();

	/**
	 * Compare the current token to a token name
	 */
	protected function is($token_name)
	{
		return ($this->token[0] == $token_name);
	}

	/**
	 * Get the current token value
	 */
	protected function value()
	{
		return $this->token[1];
	}

	/**
	 * Compare the current token to a token name, and advance if it matches.
	 */
	protected function accept($token_name)
	{
		if ($this->is($token_name))
		{
			$this->next();
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Enforce an expected token.
	 */
	protected function expect($token_name)
	{
		if ($this->accept($token_name))
		{
			return TRUE;
		}

		throw new ConditionalParserException('Unexpected ' . $this->token[0] . ' (' . $this->token[1] . ') expected ' . $token_name . '.');
	}

	/**
	 * Move to the next token.
	 */
	protected function next()
	{
		$this->token = array_shift($this->tokens);
	}
}

class Conditional_statement {

	protected $parser;

	protected $last_could_eval = TRUE;
	protected $all_previous_could_eval = TRUE;

	protected $last_result = TRUE;
	protected $output_has_if = FALSE;
	protected $done = FALSE; // true if no more should be printed

	public function __construct(Conditional_parser $parser)
	{
		$this->parser = $parser;
	}

	public function add_if($condition, $can_eval)
	{
		if ($can_eval)
		{
			$this->evaluate($condition);
		}
		else
		{
			$this->output_condition($condition);
		}

		$this->set_last_could_eval($can_eval);

		return $this->should_add_body();
	}

	public function add_elseif($condition, $can_eval)
	{
		if ($this->is_done())
		{
			return;
		}

		if ($can_eval)
		{
			$result = $this->evaluate($condition);

			// If not all previous ones have evaluated, then we can't
			// make a determination on a true branch since a previous may also
			// be true, rendering this one moot. We'll output an easily parsable
			// alternative for the next pass
			if ( ! $this->all_previous_could_eval && $result == TRUE)
			{
				$this->output_condition('TRUE');
			}
		}
		else
		{
			$this->output_condition($condition);
		}

		$this->set_last_could_eval($can_eval);

		return $this->should_add_body();
	}


	public function add_else()
	{
		// done? don't process
		if ($this->is_done())
		{
			return;
		}

		if ( ! $this->all_previous_could_eval)
		{
			$this->parser->output('{if:else}');
		}

		$this->last_result = TRUE;
		$this->set_last_could_eval(TRUE);

		return $this->should_add_body();
	}

	public function should_add_body()
	{
		// done? definitely don't add the body
		if ($this->done)
		{
			return FALSE;
		}

		// eval'd and false? don't show the body
		if ($this->last_could_eval == TRUE && $this->last_result == FALSE)
		{
			return FALSE;
		}

		return TRUE;
	}

	public function end_if()
	{
		if ($this->output_has_if)
		{
			$this->parser->output('{/if}');
		}
	}

	protected function output_condition($condition)
	{
		// otherwise we print it.
		if ( ! $this->output_has_if)
		{
			$this->output_has_if = TRUE;
			$this->parser->output('{if ' . $condition . '}');
		}
		else
		{
			$this->parser->output('{if:elseif ' . $condition . '}');
		}
	}

	protected function is_done()
	{
		// Everything has eval'd and we've hit a true one?
		// That means we're done here.
		if ($this->all_previous_could_eval && $this->last_could_eval && $this->last_result == TRUE)
		{
			$this->done = TRUE;
		}

		return $this->done;
	}

	protected function set_last_could_eval($value)
	{
		$this->last_could_eval = $value;

		if ($value === FALSE)
		{
			$this->all_previous_could_eval = FALSE;
		}
	}

	protected function evaluate($condition)
	{
		$result = FALSE;
		eval("\$result = ((".$condition.") != '');");

		$this->last_result = (bool) $result;
		return $this->last_result;
	}
}