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
 * Bold RTE Tool
 */
class Bold_rte {

	public $info = array(
		'name'			=> 'Bold',
		'version'		=> '1.0',
		'description'	=> 'Bolds and un-bolds selected text',
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
			'rte.bold'	=> array(
				'add'		=> lang('make_bold'),
				'remove'	=> lang('remove_bold'),
				'title'		=> lang('title_bold')
			)
		);
	}

	/**
	 * JS Definition
	 *
	 * @access	public
	 */
	function definition()
	{
		ob_start(); ?>

		WysiHat.addButton('bold', {
			cssClass: 'rte-bold',
			title: 			EE.rte.bold.title,
			label: 			EE.rte.bold.add,
			'toggle-text':	EE.rte.bold.remove
		});

<?php	$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}

} // END Bold_rte

// EOF
