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
class Blockquote_rte {

	public $info = array(
		'name'			=> 'Blockquote',
		'version'		=> '1.0',
		'description'	=> 'Triggers the RTE to block quote or un-quote the selected block of text',
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
			'rte.blockquote'	=> array(
				'add'		=> lang('make_blockquote'),
				'remove'	=> lang('remove_blockquote'),
				'title'		=> lang('title_blockquote')
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

		WysiHat.addButton('blockquote', {
			cssClass: 'rte-quote',
			title:			EE.rte.blockquote.title,
			label:			EE.rte.blockquote.add,
			'toggle-text': 	EE.rte.blockquote.remove,
			handler: function(state) {
				this.toggle('blockquote');
				this.Selection.set(state.selection);
			},
			query: function() {
				var
				selection	= window.getSelection(),
				hasRange	= !! selection.rangeCount,
				el			= selection.anchorNode;

				if ( hasRange )
				{
					while ( el.nodeType != "1" )
					{
						el = el.parentNode;

						if (el == null)
						{
							break;
						}
					}
				}

				$blockquote	= $(el).parents('blockquote');
				return  !! $blockquote.length;
			}
		});

<?php	$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}

} // END Blockquote_rte

// EOF
