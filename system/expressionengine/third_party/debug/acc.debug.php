<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Debug_acc {

	var $name			= 'Debug';
	var $id				= 'debug';
	var $version		= '1.0';
	var $description	= 'Debug Accessory';
	var $sections		= array();

	protected $_has_result;
	
	// --------------------------------------------------------------------

	/**
	 * Set Sections
	 *
	 * @access	public
	 * @return	void
	 */
	function set_sections()
	{
		$this->_javascript();
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
		$source = get_instance()->input->post('code');
		$code = $this->_parse_code($source);

		ob_start();
		eval($code);
		/*
		// @todo limited scope? what happens to $this?
		$run = function($code)
		{
			eval($code);
		}
		*/
		$buffer = ob_get_contents();
		@ob_end_clean();

		$buffer2 = '';

		// no output of its own, let's dump our
		// debug variable
		if ( ! $buffer && $this->_has_result)
		{
			ob_start();
			var_dump($_debug_result);
			$buffer2 = ob_get_contents();
			@ob_end_clean();
		}

		echo $buffer2."\n".$buffer;
		exit;
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
						$last_stmt_begin_ln = $t[2];
						$end_moved = TRUE;
					}
				}
			}
			else
			{
				switch ($t)
				{
					case '(':	$p_depth++;
						break;
					case ')':	$p_depth--;
						break;
					case '{':	$b_depth++;
						break;
					case '}':	$b_depth--;
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

		$this->_has_result = TRUE;
		$operator = current(explode(' ', $last));

		// add a debug variable assignment for non-results
		if ($operator != 'print' && $operator != 'echo')
		{
			$last = '$_debug_result = '.$last;
		}
		else
		{
			$this->_has_result = FALSE;
		}

		// sometimes we end on a ) or }
		if ($end_moved || ($p_depth + $b_depth + $s_depth))
		{
			$last_stmt_end_ln = count($lines);
		}

		// add a semi-colon
		$lines[$last_stmt_end_ln - 1] .= ';';

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
	 * Load up the js
	 *
	 * Hook up events, allow tabs, and bind cmd+enter to submit.
	 *
	 * @access	private
	 * @return	void
	 */
	private function _javascript()
	{
		ob_start();
?>

var acc = $('#debug'),
	form = acc.find('form'),
	code = acc.find('textarea'),
	code_el = code.get(0),
	result = acc.find('.accessorySection').eq(1);

result.hide();

code.keydown(function (e) {
	if (e.keyCode == 9) { // tab
		if ('selectionStart' in code_el) {
			var newStart = code_el.selectionStart + "\t".length;

			code_el.value = code_el.value.substr(0, code_el.selectionStart) +
							"\t" +
							code_el.value.substr(code_el.selectionEnd, code_el.value.length);
			code_el.setSelectionRange(newStart, newStart);
		}
		else if (document.selection) {
			document.selection.createRange().text = "\t";
		}

		return false;
	}

	if (e.keyCode == 13 && (e.metaKey || e.ctrlKey))
	{
		form.triggerHandler('submit');
		return false;
	}
});

form.submit(function() {
	var url = this.action;
	result.fadeOut('fast');

	$.post(url, {code: code.val()}, function(res) {
		result.find('div').html(res);
		result.fadeIn('fast');
	});

	return false;
});

<?php
		$buffer = ob_get_contents();
		@ob_end_clean();
		get_instance()->javascript->output($buffer);
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