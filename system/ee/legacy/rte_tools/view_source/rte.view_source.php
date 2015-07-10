<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.5
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine View Source RTE Tool
 *
 * @package		ExpressionEngine
 * @subpackage	RTE
 * @category	RTE
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class View_source_rte {

	public $info = array(
		'name'			=> 'View Source',
		'version'		=> '1.0',
		'description'	=> 'Triggers the RTE to switch to and from view source mode',
		'cp_only'		=> 'n'
	);

	/**
	 * Javascript globls we need
	 *
	 * @access	public
	 */
	function globals()
	{
		ee()->lang->loadfile('rte');
		return array(
			'rte.view_source'	=> array(
				'code'		=> lang('view_code'),
				'content'	=> lang('view_content'),
				'title'		=> lang('title_view')
			)
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Styles
	 *
	 * @access	public
	 */
	function styles()
	{
		ob_start(); ?>

		.WysiHat-editor-toolbar .view_source {
			text-transform: uppercase;
		}

<?php	$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
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

		WysiHat.addButton('view_source', {
			cssClass: 'rte-view',
			title:			EE.rte.view_source.title,
			label:			EE.rte.view_source.code,
			'toggle-text':	EE.rte.view_source.content,
			handler: function() {
				this.Editor.updateField();
				this.Commands.toggleHTML(this);
			}
		});

<?php	$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}

} // END View_source_rte

/* End of file rte.view_source.php */
/* Location: ./system/expressionengine/rte_tools/view_source/rte.view_source.php */