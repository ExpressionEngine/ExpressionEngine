<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @link		http://ellislab.com
 */
class Underline_rte {

	public $info = array(
		'name'			=> 'Underline',
		'version'		=> '1.0',
		'description'	=> 'Underlines and de-underlines text',
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
			label:			EE.rte.underline.add,
			'toggle-text':	EE.rte.underline.remove
		});
		
<?php	$buffer = ob_get_contents();
		ob_end_clean(); 
		return $buffer;
	}

} // END Underline_rte

/* End of file rte.underline.php */
/* Location: ./system/expressionengine/rte_tools/underline/rte.underline.php */