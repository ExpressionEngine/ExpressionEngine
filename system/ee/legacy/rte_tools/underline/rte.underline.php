<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Underline RTE Tool
 */
class Underline_rte {

	public $info = array(
		'name'			=> 'Underline',
		'version'		=> '1.0',
		'description'	=> 'Underlines and de-underlines text',
		'cp_only'		=> 'n'
	);

	/**
	 * Globals we need
	 *
	 * @access	public
	 */
	function globals()
	{
		ee()->lang->loadfile('rte');
		return array(
			'rte.underline'	=> array(
				'add'		=> lang('make_underline'),
				'remove'	=> lang('remove_underline')
			)
		);
	}

	/**
	 * Javascript Definition
	 *
	 * @access	public
	 */
	function definition()
	{
		ob_start(); ?>

		WysiHat.addButton('underline', {
			cssClass: 'rte-underline',
			label:			EE.rte.underline.add,
			'toggle-text':	EE.rte.underline.remove
		});

<?php	$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}

} // END Underline_rte

// EOF
