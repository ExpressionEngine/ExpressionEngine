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
	 * @return	void
	 */
	function process_run()
	{
		$code = get_instance()->input->post('code');

		$lines = explode("\n", $code);

		$last = array_pop($lines);
		$operator = current(explode(' ', $last));
		$has_result = TRUE;

		if ($operator != 'print' && $operator != 'echo')
		{
			array_push($lines, '$_debug_result = '.$last.';');
		}
		else
		{
			$has_result = FALSE;
			array_push($lines, $last.';');
		}

		$code = implode("\n", $lines);

		ob_start();
		eval($code);
		$buffer = ob_get_contents();
		@ob_end_clean();

		$buffer2 = '';

		if ( ! $buffer && $has_result)
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
	 * Build the simple form
	 *
	 * @access	public
	 * @return	void
	 */
	function _form()
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
	 * @access	public
	 * @return	void
	 */
	function _javascript()
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
	function __get($var)
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