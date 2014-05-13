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

		$this->next(); // go go go
		$this->template();
		// $this->expect('EOS'); // todo?

		return $this->closeBuffer();
	}

	public function setVariables($vars)
	{
		$this->variables = $vars;
	}

	public function safetyOn()
	{
		$this->safety = TRUE;
	}

	/**
	 * Template production rule
	 */
	protected function template()
	{
		if ($this->is('TEMPLATE_STRING'))
		{
			$this->output($this->value());
			$this->next();
			$this->template();
		}
		elseif ($this->accept('IF'))
		{
			$this->conditional();
			$this->expect('ENDIF');
			$this->output('{/if}');
			$this->template();
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
	protected function conditional()
	{
		$cond = '';
		$this->openBuffer();

		$this->condition();

		$this->output('{if ' . $this->closeBuffer() . '}');

		$this->template();

		while ($this->accept('ELSEIF'))
		{
			$this->openBuffer();
			$this->condition();

			$this->output('{if:elseif ' . $this->closeBuffer() . '}');

			$this->template();
		}

		if ($this->accept('ELSE'))
		{
			$this->output('{if:else}');
			$this->template();
		}

	}

	/**
	 * The condition and closing brace.
	 *
	 * 5 == 7 && bob - mary}
	 */
	protected function condition()
	{
		$this->expression();
		$this->expect('ENDCOND');
	}

	/**
	 * Boolean Expressions
	 */
	protected function expression()
	{
		if ($this->accept('LP'))
		{
			$this->output('(');

			$this->output($this->expression());
			$this->expect('RP');

			$this->output(')');
		}
		elseif ($this->is('STRING') || $this->is('NUMBER') || $this->is('BOOL'))
		{
			$this->output($this->scalar($this->value()));
			$this->next();
		}
		elseif ($this->is('VARIABLE'))
		{
			$this->output($this->variable($this->value()));
			$this->next();
		}
		elseif ($this->is('TAG'))
		{
			$this->output($this->tag($this->value()));
			$this->next();
		}
		elseif ($this->is('MISC'))
		{
			$this->output($this->misc($this->value()));
			$this->next();
		}

		if ($this->is('OPERATOR'))
		{
			$this->output($this->value());
			$this->next();
			$this->output($this->expression());
		}
	}

	/**
	 * Scalar Values
	 */
	protected function scalar($value)
	{
		if (is_bool($value))
		{
			return $value ? 'TRUE' : 'FALSE';
		}

		if (is_numeric($value))
		{
			return $value;
		}

		return '"' . $value . '"';
	}

	/**
	 * Variable Values
	 */
	protected function variable($name)
	{
		if (array_key_exists($name, $this->variables))
		{
			return $this->scalar(
				$this->encode(
					$this->variables[$name]
				)
			);
		}

		if ($this->safety === TRUE)
		{
			return 'FALSE';
		}

		return $name;
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
	 * Encode a variable
	 */
	protected function encode($value)
	{
		$value = str_replace(
			array("'", '"', '(', ')', '$', "\n", "\r", '\\'),
			array('&#39;', '&#34;', '&#40;', '&#41;', '&#36;', '', '', '&#92;'),
			$value
		);

		return str_replace(
			array('{', '}',),
			array('&#123;', '&#125;',),
			$value
		);
	}

	/**
	 * Add to the current output buffer
	 */
	protected function output($value)
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
			$this->output(' ');//$this->token[1]);
			$this->next();
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
		return $out;
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
	 * Helper functions
	 */

	protected function is($token_name)
	{
		return ($this->token[0] == $token_name);
	}

	protected function value()
	{
		return $this->token[1];
	}

	protected function accept($token_name)
	{
		if ($this->is($token_name))
		{
			$this->next();
			return TRUE;
		}

		return FALSE;
	}

	protected function expect($token_name)
	{
		if ($this->accept($token_name))
		{
			return TRUE;
		}

		throw new Exception('Unexpected ' . $this->token[0] . ' expected ' . $token_name . '.');
	}

	protected function next()
	{
		$this->token = array_shift($this->tokens);
	}
}