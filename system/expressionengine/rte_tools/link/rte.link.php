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
 * ExpressionEngine Link RTE Tool
 *
 * @package		ExpressionEngine
 * @subpackage	RTE
 * @category	RTE
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
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
		ee()->lang->loadfile('rte');
		return array(
			'rte.link'	=> array(
				'add'		=> lang('link'),
				'dialog'	=> array(
						'title'				=> lang('link'),
						'url_field_label'	=> lang('rte_url'),
						'title_field_label'	=> lang('rte_title'),
						'rel_field_label'	=> lang('rte_relationship'),
						'selection_error'	=> lang('rte_selection_error'),
						'url_required'		=> lang('valid_url_required'),
						'add_link'			=> lang('add_link'),
						'update_link'		=> lang('update_link'),
						'remove_link'		=> lang('remove_link'),
						'external_link'		=> lang('external_link')
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
			'ui'	=> array('dialog', 'position')
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

		#rte-link-dialog p {
			margin:10px 0;
		}

		#rte-link-dialog label {
			display: inline-block;
		}

		#rte-link-dialog input[type=\"text\"]
		{
			width: 100%;
			-moz-box-sizing: border-box;
			-webkit-box-sizing: border-box;
			box-sizing: border-box;
			padding: 4px;
		}

		#rte-link-dialog .buttons {
			margin: 10px 0 8px;
			float: right;
		}

		#rte-link-dialog .submit {
			cursor: pointer;
		}

		#rte-link-dialog .notice {
			color: #CE0000;
			font-weight: bold;
			margin: 5px 0;
		}

		#rte-remove-link {
			cursor: pointer;
			margin-right: 1em;
		}

		#rte-remove-link:hover {
			text-decoration: underline;
		}

		#rte-link-dialog-external {
			margin-top: 10px;
		}

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