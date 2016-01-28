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
 * ExpressionEngine Underline RTE Tool
 *
 * @package		ExpressionEngine
 * @subpackage	RTE
 * @category	RTE
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
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

	// --------------------------------------------------------------------

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
