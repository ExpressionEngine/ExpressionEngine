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
 * ExpressionEngine Ordered List RTE Tool
 *
 * @package		ExpressionEngine
 * @subpackage	RTE
 * @category	RTE
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Ordered_list_rte {
	
	public $info = array(
		'name'			=> 'Ordered List',
		'version'		=> '1.0',
		'description'	=> 'Triggers the RTE to make the selected blocks into ordered list items',
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
			'rte.ordered_list'	=> array(
				'add'		=> lang('make_ol'),
				'remove'	=> lang('remove_ol')
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
		
		WysiHat.addButton('ordered_list', {
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

/* End of file rte.ordered_list.php */
/* Location: ./system/expressionengine/rte_tools/ordered_list/rte.ordered_list.php */