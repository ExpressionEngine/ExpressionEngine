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
 * Link RTE Tool
 */
class Link_rte {

	public $info = array(
		'name'			=> 'Link',
		'version'		=> '1.0',
		'description'	=> 'Triggers the RTE to link the selected text',
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
			'rte.link'	 => array(
				'add'    => lang('link'),
				'title'  => lang('title_link'),
				'modal'  => array(
					'html'            => ee('View')->make('rte:link')->render(array()),
					'url_required'    => lang('valid_url_required'),
					'selection_error' => lang('rte_selection_error'),
					'add_link'        => lang('add_link'),
					'update_link'     => lang('update_link'),
				)
			)
		);
	}

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

// EOF
