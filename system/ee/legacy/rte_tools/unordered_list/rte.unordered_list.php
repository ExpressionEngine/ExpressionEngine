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
 * Unordered List RTE Tool
 */
class Unordered_list_rte {

	public $info = array(
		'name'			=> 'Unordered List',
		'version'		=> '1.0',
		'description'	=> 'Triggers the RTE to make the selected blocks into unordered list items',
		'cp_only'		=> 'n'
	);

	/**
	 * Globals we need
	 *
	 * @access	public
	 * @return	mixed array of globals
	 */
	function globals()
	{
		ee()->lang->loadfile('rte');
		return array(
			'rte.unordered_list'	=> array(
				'add'		=> lang('make_ul'),
				'remove'	=> lang('remove_ul'),
				'title'		=> lang('title_ul')
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

		WysiHat.addButton('unordered_list', {
			cssClass: 'rte-list',
			title:			EE.rte.unordered_list.title,
			label:			EE.rte.unordered_list.add,
			'toggle-text':	EE.rte.unordered_list.remove,
			handler: function(state){
				this.make('unorderedList');
			},
			query: function(){
				return this.is('unorderedList');
			}
		});

<?php	$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}

} // END Unordered_list_rte

// EOF
