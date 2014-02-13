<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.5
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Bold RTE Tool
 *
 * @package		ExpressionEngine
 * @subpackage	RTE
 * @category	RTE
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Bold_rte {

	public $info = array(
		'name'			=> 'Bold',
		'version'		=> '1.0',
		'description'	=> 'Bolds and un-bolds selected text',
		'cp_only'		=> 'n'
	);

	private $EE;

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function __construct()
	{
		// Make a local reference of the ExpressionEngine super object
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

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
				'remove'	=> lang('remove_bold')
			)
		);
	}

	// --------------------------------------------------------------------

	/**
	 * JS Definition
	 *
	 * @access	public
	 */
	function definition()
	{
		ob_start(); ?>

		WysiHat.addButton('bold', {
			label: 			EE.rte.bold.add,
			'toggle-text':	EE.rte.bold.remove
		});

<?php	$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}

} // END Bold_rte

/* End of file rte.bold.php */
/* Location: ./system/expressionengine/rte_tools/bold/rte.bold.php */