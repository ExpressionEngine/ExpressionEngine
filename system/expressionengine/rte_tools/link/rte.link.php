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
	'rte_tool_name'				=> 'Link',
	'rte_tool_version'			=> '1.0',
	'rte_tool_author'			=> 'Aaron Gustafson',
	'rte_tool_author_url'		=> 'http://easy-designs.net/',
	'rte_tool_description'		=> 'Triggers the RTE to link the selected text',
	'rte_tool_definition'		=> Link_rte::definition()
);

Class Link_rte {
	
	private $EE;
	
	# should this be shown on the frontend?
	public	$frontend = 'y';
		
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
			'rte.link'	=> array(
				'add'		=> lang('make_link'),
				'dialog'	=> array(
						'title'				=> lang('rte_link_preferences'),
						'url_field_label'	=> lang('url'),
						'title_field_label'	=> lang('title'),
						'rel_field_label'	=> lang('relationship'),
						'submit_button'		=> lang('submit'),
						'selection_error'	=> lang('selection_error'),
						'url_required'		=> lang('valid_url_required')
				)
			)
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