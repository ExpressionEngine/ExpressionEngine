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
 * ExpressionEngine Link RTE Tool
 *
 * @package		ExpressionEngine
 * @subpackage	RTE
 * @category	RTE
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Link_rte {
	
	public $info = array(
		'name'			=> 'Link',
		'version'		=> '1.0',
		'description'	=> 'Triggers the RTE to link the selected text',
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
			'rte.link'	=> array(
				'add'		=> lang('link'),
				'dialog'	=> array(
						'title'				=> lang('link'),
						'url_field_label'	=> lang('url'),
						'title_field_label'	=> lang('title'),
						'rel_field_label'	=> lang('relationship'),
						'selection_error'	=> lang('selection_error'),
						'url_required'		=> lang('valid_url_required'),
						'add_link'			=> lang('add_link'),
						'update_link'		=> lang('update_link'),
						'remove_link'		=> lang('remove_link')
				)
			)
		);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Libraries we need
	 *
	 * @access	public
	 */
	function libraries()
	{
		return array(
			'ui'	=> 'dialog'
		);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Styles we need
	 *
	 * @access	public
	 */
	function styles()
	{
		ob_start(); ?>

		#rte_link_dialog p { margin-bottom:10px; }
		#rte_link_dialog label { width: 90px; display: inline-block; }
		#rte_link_dialog input, #rte_link_dialog select { width: 70%; margin-left: 10px; }
		#rte_link_dialog .buttons { text-align: center; }
		#rte_link_dialog button { cursor: pointer; }

<?php	$buffer = ob_get_contents();
		ob_end_clean(); 
		return $buffer;
	}

	// --------------------------------------------------------------------

	/**
	 * JS Defintion
	 *
	 * @access	public
	 */
	function definition()
	{
		# load the external file
		return file_get_contents( 'rte.link.js', TRUE );
	}

} // END Link_rte

/* End of file rte.link.php */
/* Location: ./system/expressionengine/rte_tools/link/rte.link.php */