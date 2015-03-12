<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Debug_acc {

	var $name			= 'Debug';
	var $id				= 'debug';
	var $version		= '1.0';
	var $description	= 'Debug Accessory';
	var $sections		= array();
	
	// --------------------------------------------------------------------

	/**
	 * Set Sections
	 *
	 * @access	public
	 * @return	void
	 */
	function set_sections()
	{
		$this->cp->load_package_js('run');
		$this->sections = array(
			'Code' => $this->_form(),
			'Result' => '<div id="'.$this->id.'_result" style="background: #fff; color: #555; padding:5px 10px"></div>'
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Do some eval magic
	 *
	 * @access	public
	 * @return	string	eval'd results
	 */
	public function process_run()
	{
		$source = ee()->input->post('code');
		$code = $this->_parse_code($source);

		ob_start();
		eval($code);
		$buffer = ob_get_contents();
		@ob_end_clean();

		$buffer2 = '';

		// no output of its own, let's dump our
		// debug variable
		if ( ! $buffer && isset($_debug_result))
		{
			ob_start();
			$this->_dump_result($_debug_result);
			$buffer2 = ob_get_contents();
			@ob_end_clean();
		}

		echo $buffer2."\n".$buffer;
		exit;
	}

	// --------------------------------------------------------------------

	/**
	 * Dump the result variable
	 *
	 * Does some extra magic to show class and function reflections
	 *
	 * @access	private
	 * @return	string	dump
	 */
	private function _dump_result($result)
	{
		if ( ! (is_object($result) && $result instanceof Reflector))
		{
			var_dump($result);
			return;
		}

		$pad = '&nbsp;&nbsp&nbsp';

		if ($result instanceof ReflectionClass)
		{
			echo '<strong>Class:</strong><br>';
			echo $pad.$result->getName().'<br>';

			foreach (array('Properties', 'Methods') as $members)
			{
				$parts = $result->{'get'.$members}();

				if (empty($parts))
				{
					continue;
				}

				echo '<br>';
				echo '<strong>'.$members.':</strong><br>';

				foreach ($parts as $part)
				{
					echo $pad;
					echo $part->name;
					echo ' <span style="color: #aaa">'.implode(' ', Reflection::getModifierNames($part->getModifiers())).'</span>';
					echo '<br>';
				}
			}

			return;
		}

		if ($result instanceof ReflectionFunction)
		{
			echo '<strong>Function:</strong><br>';
			echo $pad.$result->getName().'<br>';

			foreach (array('Parameters') as $members)
			{
				$parts = $result->{'get'.$members}();

				if (empty($parts))
				{
					continue;
				}

				echo '<br>';
				echo '<strong>'.$members.':</strong><br>';

				foreach ($parts as $part)
				{
					echo $pad;
					echo $part->name;
					if ($part->isOptional())
					{
						echo ' <span style="color: #aaa">'.var_export($part->getDefaultValue(), TRUE).'</span>';
					}
					echo '<br>';
				}

			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Prep code for eval
	 *
	 * Parses the code to allow us to var_dump the result of the last
	 * statement, if there is no other output results from the code.
	 *
	 * @access	public
	 * @return	string	eval'd results
	 */
	private function _parse_code($code)
	{
		// @todo do a reflection if it's a function or class
		// and show it in a collapsed state
		// Class Name
		//	-> interfaces (collapse)
		//	-> methods
		//	-> properties

		$tokens = token_get_all('<?php '.$code);

		$b_depth = 0;		// block depth
		$p_depth = 0;		// parenthesis depth
		$s_depth = 0;		// square bracket depth

		$last_stmt_begin_token = NULL;
		$last_stmt_begin_ln = 0;
		$last_stmt_end_ln = 0;

		$end_moved = FALSE;

		foreach ($tokens as $t)
		{
			if (is_array($t))
			{
				// always ignore comments
				if ($t[0] == T_COMMENT || $t[0] == T_DOC_COMMENT)
				{
					continue;
				}

				// move ends if we're back to root
				if (($p_depth + $b_depth + $s_depth) === 0)
				{
					// ignore end movements for consecutive whitespace
					// and comments, as well as previous semicolons
					if ($end_moved)
					{
						$last_stmt_end_ln = $t[2];
						$end_moved = FALSE;
					}

					// ignore all whitespace when moving the beginning
					if ($t[0] != T_WHITESPACE)
					{
						$last_stmt_begin_token = $t;
						$last_stmt_begin_ln = $t[2];
						$end_moved = TRUE;
					}
				}
			}
			else
			{
				if ($end_moved)
				{
					$last_stmt_begin_token = $t;
				}

				switch ($t)
				{
					case '(':	$p_depth++;
						break;
					case ')':	$p_depth--;
						break;
					case '{':	$b_depth++;
						break;
					case '}':	$b_depth--;
								$end_moved = TRUE;
						break;
					case '[':	$s_depth++;
						break;
					case ']':	$s_depth--;
						break;
					case ';':	$end_moved = TRUE;
						break;
				}
			}
		}

		$lines = explode("\n", $code);
		$last =& $lines[$last_stmt_begin_ln - 1];

		// sometimes we end on a ) or }
		if ($end_moved || ($p_depth + $b_depth + $s_depth))
		{
			$last_stmt_end_ln = count($lines);
		}

		// add a semi-colon
		$lines[$last_stmt_end_ln - 1] .= ';';


		// add debug variable assignment for non-results

		list($operator, $keyword) = explode(' ', trim($last).' ');
		$keyword = preg_replace('/^(\w+).*/is', '$1', $keyword);

		$reflectable = array('class', 'function');
		$lang_constructs = array('print', 'echo', 'eval');

		if (in_array($operator, $reflectable))
		{
			$lines[] = '$_debug_result = new Reflection'.ucfirst($operator).'("'.$keyword.'");';
		}
		elseif ( ! in_array($operator, $lang_constructs))
		{
			$last = '$_debug_result = '.$last;
		}

		// reassemble prepped code
		return implode("\n", $lines);
	}

	// --------------------------------------------------------------------

	/**
	 * Build the simple form
	 *
	 * @access	private
	 * @return	string	form
	 */
	private function _form()
	{
		return
			form_open('C=addons_accessories'.AMP.'M=process_request'.AMP.'accessory=debug'.AMP.'method=process_run').
			form_textarea($this->id.'_code', '').
			BR.
			form_submit('run', 'Run').
			form_close();
	}

	// --------------------------------------------------------------------

	/**
	 * Magic method to automagically load libraries and models.
	 *
	 * @access	public
	 * @return	void
	 */
	public function __get($var)
	{
		$EE =& get_instance();
		
		if ( ! isset($this->$var))
		{
			if (strpos($var, '_model'))
			{
				$EE->load->model($var);
			}
			else
			{
				$EE->load->library($var);
			}
		}

		return $EE->$var;
	}
}

/* End of file acc.debug.php */
/* Location: system/expressionengine/third_party/debug/acc.debug.php */