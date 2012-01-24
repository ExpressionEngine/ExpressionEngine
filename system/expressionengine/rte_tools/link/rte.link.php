<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
=====================================================
 ExpressionEngine - by EllisLab
-----------------------------------------------------
 http://expressionengine.com/
-----------------------------------------------------
 Copyright (c) 2004 - 2011 EllisLab, Inc.
=====================================================
 THIS IS COPYRIGHTED SOFTWARE
 PLEASE READ THE LICENSE AGREEMENT
 http://expressionengine.com/user_guide/license.html
=====================================================
 File: rte.link.php
-----------------------------------------------------
 Purpose: Link RTE Tool
=====================================================

*/

$rte_tool_info = array(
	'rte_name'			=> 'Link',
	'rte_version'		=> '1.0',
	'rte_author'		=> 'Aaron Gustafson',
	'rte_author_url'	=> 'http://easy-designs.net/',
	'rte_description'	=> 'Triggers the RTE to link the selected text',
	'rte_definition'	=> Link_rte::definition()
);

Class Link_rte {
	
	private $EE;
	
	/** -------------------------------------
	/**  Constructor
	/** -------------------------------------*/
	function __construct()
	{
		// Make a local reference of the ExpressionEngine super object
		$this->EE =& get_instance();
	}

	/** -------------------------------------
	/**  Globals we need defined
	/** -------------------------------------*/
	function globals()
	{
		$this->EE->lang->loadfile('rte');
		return array(
			'rte.link.add'						=> lang('make_link'),
			'rte.link_dialog.title'				=> lang('rte_link_preferences'),
			'rte.link_dialog.url_field_label'	=> lang('url'),
			'rte.link_dialog.title_field_label'	=> lang('title'),
			'rte.link_dialog.rel_field_label'	=> lang('relationship'),
			'rte.link_dialog.submit_button'		=> lang('submit'),
			'rte.link_dialog.selection_error'	=> lang('selection_error'),
			'rte.link_dialog.url_required'		=> lang('valid_url_required')
		);
	}
	
	/** -------------------------------------
	/**  Libraries we need loaded
	/** -------------------------------------*/
	function libraries()
	{
		return array(
			'ui'	=> 'dialog'
		);
	}
	
	/** -------------------------------------
	/**  Styles we need loaded
	/** -------------------------------------*/
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

	/** -------------------------------------
	/**  RTE Tool Definition
	/** -------------------------------------*/
	function definition()
	{
		# load the external file
		return file_get_contents( 'rte.link.js', TRUE );
	}

} // END Link_rte

/* End of file rte.link.php */
/* Location: ./system/expressionengine/rte_tools/link/rte.link.php */