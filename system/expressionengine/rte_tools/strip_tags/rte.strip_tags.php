<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.5
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Strip Tags RTE Tool
 *
 * @package		ExpressionEngine
 * @subpackage	RTE
 * @category	RTE
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Strip_tags_rte {
	
	public $info = array(
		'name'			=> 'Strip Tags',
		'version'		=> '1.0',
		'description'	=> 'Triggers the RTE to strip all block and phrase-level formatting elements',
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
		$this->EE->lang->loadfile('rte');
		return array(
			'rte.strip_tags.label' => lang('strip_tags')
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
		
		toolbar.addButton({
			name:	"strip_tags",
			label:	EE.rte.strip_tags.label,
			handler: function( $ed ){
				$ed.stripFormattingElements();
				$ed.unformatContentBlock();
			}
		});
		
<?php	$buffer = ob_get_contents();
		ob_end_clean(); 
		return $buffer;
	}

} // END Strip_tags_rte

/* End of file rte.strip_tags.php */
/* Location: ./system/expressionengine/rte_tools/strip_tags/rte.strip_tags.php */