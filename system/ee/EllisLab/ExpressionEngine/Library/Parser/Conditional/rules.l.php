<?php

namespace EllisLab\ExpressionEngine\Library\Parser\Conditional;

use EllisLab\ExpressionEngine\Library\Parser\Conditional\Token\Token;
use EllisLab\ExpressionEngine\Library\Parser\Conditional\Token\Boolean;
use EllisLab\ExpressionEngine\Library\Parser\Conditional\Token\Comment;
use EllisLab\ExpressionEngine\Library\Parser\Conditional\Token\Number;
use EllisLab\ExpressionEngine\Library\Parser\Conditional\Token\Operator;
use EllisLab\ExpressionEngine\Library\Parser\Conditional\Token\Other;
use EllisLab\ExpressionEngine\Library\Parser\Conditional\Token\StringLiteral;
use EllisLab\ExpressionEngine\Library\Parser\Conditional\Token\Tag;
use EllisLab\ExpressionEngine\Library\Parser\Conditional\Token\Variable;


/**
 * Helper function to create the operator pattern which is somewhat
 * complicated.
 */
function compileOperatorPattern()
{
	$pattern = '';
	$operators = array(
		'^=', '*=', '$=', '~',
		'==', '!=', '<=', '>=', '<>', '<', '>',
		'**', '%', '+', '-', '*', '/',
		'.', '!', '^',
		'||', '&&',
		'AND', 'OR', 'XOR'
	);

	foreach ($operators as $operator)
	{
		$operator = preg_quote($operator, '/');
		$pattern .= $operator.'|';
	}

	return $pattern = '('.substr($pattern, 0, -1).')';
}


/**
 * Conditional Lexer Rules
 *
 * Syntax follows basic lex syntax.
 *
 * All start conditions are exclusive.
 */

return array(

	// comments are valid anywhere
	'/{!--(.*?)--}/' => function($lexeme, $scanner)
	{
		return new Token('COMMENT', $lexeme);
	},

	// template strings
	'<INITIAL>/[^{]+/' => function($lexeme, $scanner)
	{
		return new Token('TEMPLATE_STRING', $lexeme);
	},

	// conditionals that start an expression
	'<INITIAL>/\{if(:elseif)?\s/' => function($lexeme, $scanner)
	{
		$scanner->state = 'EXPRESSION';
		$scanner->index--; // backtrack on the whitespace

		$tokens = array();
		$tokens[] = new Token('LD', '{');

		switch (trim($lexeme))
		{
			case '{if': $tokens[] = new Token('IF', 'if');
				break;
			case '{if:elseif': $tokens[] = new Token('ELSEIF', 'if:elseif');
				break;
		}

		return $tokens;
	},

	// other conditional builtins that don't start
	// an expression {/if} and {if:else}
	'<INITIAL>/{(\/if|if:else)\}/' => function($lexeme, $scanner)
	{
		$tokens = array();
		$tokens[] = new Token('LD', '{');

		switch ($lexeme)
		{
			case '{/if}': $tokens[] = new Token('ENDIF', '/if');
				break;
			case '{if:else}': $tokens[] = new Token('ELSE', 'if:else');
				break;
		}

		$tokens[] = new Token('RD', '}');
		return $tokens;
	},

	// valid in expressions

	// whitespace
	'<EXPRESSION>/\s+/' => function($lexeme, $scanner)
	{
		return new Token('WHITESPACE', $lexeme);
	},

	// booleans
	'<EXPRESSION>/(TRUE|FALSE)/i' => function($lexeme, $scanner)
	{
		return new Token('BOOL', $lexeme);
	},

	// parentheses
	'<EXPRESSION>/[()]/' => function($lexeme, $scanner)
	{
		$token = ($lexeme == '(') ? 'LP' : 'RP';
		return new Token($token, $lexeme);
	},

	// numbers
	'<EXPRESSION>/([0-9]*\.[0-9]+|[0-9]+\.[0-9]*|[0-9]+)/' => function($lexeme, $scanner)
	{
		return new Token('NUMBER', $lexeme);
	},

	// operators
	'<EXPRESSION>/'.compileOperatorPattern().'/i' => function($lexeme, $scanner)
	{
		return new Token('OPERATOR', $lexeme);
	},

	// variables
	'<EXPRESSION>/\w*([a-zA-Z]+([\w:-]+\w)?|(\w[\w:-]+)?[a-zA-Z]+)\w*/i' => function($lexeme, $scanner)
	{
		return new Token('VARIABLE', $lexeme);
	},

	// open tag
	'<EXPRESSION|TAG>/{/' => function($lexeme, $scanner)
	{
		if ($scanner->state != 'TAG')
		{
			$scanner->tag_buffer = '';
			$scanner->state = 'TAG';
			$scanner->tag_depth = 0;
		}

		$scanner->tag_depth++;
		$scanner->tag_buffer .= $lexeme;
	},

	// end expression
	'<EXPRESSION>/}/' => function($lexeme, $scanner)
	{
		$scanner->state = 'INITIAL';
		return new Token('RD', $lexeme);
	},

	// seek through tag, avoiding strings and nested
	// tags. They will be handled separately
	'<TAG>/[^{}\'"]+/' => function($lexeme, $scanner)
	{
		$scanner->tag_buffer .= $lexeme;
	},

	// end tag
	'<TAG>/}/' => function($lexeme, $scanner)
	{
		$scanner->tag_depth--;
		$scanner->tag_buffer .= $lexeme;

		if ($scanner->tag_depth == 0)
		{
			$scanner->state = 'EXPRESSION';
			return new Token('TAG', $scanner->tag_buffer);
		}
	},

	// strings are valid in tags and expressions

	// single quoted string
	"<TAG|EXPRESSION>/'(\\|\'|[^'])*?'/" => function($lexeme, $scanner)
	{
		if ($scanner->state == 'TAG')
		{
			$scanner->tag_buffer .= $lexeme;
		}
		else
		{
			return new Token('STRING', substr($lexeme, 1, -1));
		}
	},

	// double quoted string
	'<TAG|EXPRESSION>/"(\\|\"|[^"])*?"/' => function($lexeme, $scanner)
	{
		if ($scanner->state == 'TAG')
		{
			$scanner->tag_buffer .= $lexeme;
		}
		else
		{
			return new Token('STRING', substr($lexeme, 1, -1));
		}
	},

);

// EOF
