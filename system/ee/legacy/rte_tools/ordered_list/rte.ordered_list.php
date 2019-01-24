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
 * Ordered List RTE Tool
 */
class Ordered_list_rte {

	public $info = array(
		'name'			=> 'Ordered List',
		'version'		=> '1.0',
		'description'	=> 'Triggers the RTE to make the selected blocks into ordered list items',
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
			'rte.ordered_list'	=> array(
				'add'		=> lang('make_ol'),
				'remove'	=> lang('remove_ol'),
				'title'		=> lang('title_ol'),
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

		WysiHat.addButton('ordered_list', {
			cssClass: 'rte-order-list',
			title:			EE.rte.ordered_list.title,
			label:			EE.rte.ordered_list.add,
			'toggle-text':	EE.rte.ordered_list.remove,
			handler: function(state) {
				this.make('orderedList');
			},
			query: function() {
				return this.is('orderedList');
			}
		});

<?php	$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}

} // END Ordered_list_rte

// EOF
