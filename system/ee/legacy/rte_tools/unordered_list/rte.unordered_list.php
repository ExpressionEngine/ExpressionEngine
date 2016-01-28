<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.5
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Unordered List RTE Tool
 *
 * @package		ExpressionEngine
 * @subpackage	RTE
 * @category	RTE
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
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

	// --------------------------------------------------------------------

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
