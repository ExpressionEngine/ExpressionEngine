<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.8.2
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Core Conditional Helper Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Conditional_util {

	private $debug = FALSE;
	private $protect_javascript = TRUE;

	public function disable_protect_javascript()
	{
		$this->protect_javascript = FALSE;
	}

	public function enable_debug()
	{
		$this->debug = TRUE;
	}

	/**
	 * Prep conditionals
	 *
	 * @access	public
	 * @param	string $str		The template string containing conditionals
	 * @param	string $vars	The variables to look for in the conditionals
	 * @param	string $safety	If TRUE, make sure conditionals are fully
	 *							parseable by replacing unknown variables with
	 *							FALSE. This defaults to FALSE so that conditionals
	 *							are slowly filled and then turned into safely
	 *							executable ones with the safety on at the end.
	 * @param	string $prefix	Prefix for the variables in $vars.
	 * @return	string The new template to use instead of $str.
	 */
	public function prep_conditionals($str, $vars, $safety = FALSE, $prefix = '')
	{
		if (isset(ee()->TMPL->embed_vars))
		{
			// If this is being called from a module tag, embedded variables
			// aren't going to be available yet.  So this is a quick workaround
			// to ensure advanced conditionals using embedded variables can do
			// their thing in mod tags.
			$vars = array_merge($vars, ee()->TMPL->embed_vars);
		}

		// Protect compressed javascript from being mangled or interpreted as invalid
		if ($this->protect_javascript !== FALSE)
		{
			$protected_javascript = array();
			$js_protect = unique_marker('tmpl_script');

			if (stristr($str, '<script') && preg_match_all('/<script.*?>.*?<\/script>/is', $str, $matches))
			{
				foreach ($matches[0] as $i => $match)
				{
					$protected_javascript[$js_protect.$i] = $match;
				}

				$str = str_replace(array_values($protected_javascript), array_keys($protected_javascript), $str);
			}
		}

		// Prefix passed in variables
		$prefixed_vars = array();

		foreach ($vars as $key => $var)
		{
			$prefixed_vars[$prefix.$key] = $var;
		}

		$vars = $prefixed_vars;

		require_once APPPATH.'libraries/template/Conditional_lexer.php';

		$lexer = new Conditional_lexer();

		// Get the token stream
		$tokens = $lexer->tokenize($str);

		require_once APPPATH.'libraries/template/Conditional_parser.php';

		$parser = new Conditional_parser($tokens);
		$parser->setVariables($vars);

		if ($safety === TRUE)
		{
			$parser->safetyOn();
		}

		$output = $parser->parse();

		// Unprotect <script> tags
		if ($this->protect_javascript !== FALSE && count($protected_javascript) > 0)
		{
			$output = str_replace(array_keys($protected_javascript), array_values($protected_javascript), $output);
		}

		return $output;
	}
}

class ConditionalException extends Exception {}
class ConditionalLexerException extends ConditionalException {}
class ConditionalParserException extends ConditionalException {}