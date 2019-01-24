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
 * Italic RTE Tool
 */
class Italic_rte {

	public $info = array(
		'name'			=> 'Italic',
		'version'		=> '1.0',
		'description'	=> 'Italicizes and de-italicizes text',
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
			'rte.italics'	=> array(
				'add'		=> lang('make_italics'),
				'remove'	=> lang('remove_italics'),
				'title'		=> lang('title_italics'),
			)
		);
	}

	/**
	 * JS Defintion
	 *
	 * @access	public
	 */
	function definition()
	{
		ob_start(); ?>

		WysiHat.addButton('italic', {
			cssClass: 'rte-italic',
			title:			EE.rte.italics.title,
			label:			EE.rte.italics.add,
			'toggle-text':	EE.rte.italics.remove
		});

<?php	$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}

} // END Italic_rte

// EOF
