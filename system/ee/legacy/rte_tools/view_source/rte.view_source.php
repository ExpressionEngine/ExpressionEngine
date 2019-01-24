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
 * View Source RTE Tool
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

