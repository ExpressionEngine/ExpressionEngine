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
 * ExpressionEngine Core Conditional Statement Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
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