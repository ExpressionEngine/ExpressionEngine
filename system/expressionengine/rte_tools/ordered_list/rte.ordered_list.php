<?php

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
 File: rte.ordered_list.php
-----------------------------------------------------
 Purpose: Ordered List RTE Tool
=====================================================

*/

$rte_tool_info = array(
	'rte_name'			=> 'Ordered List',
	'rte_version'		=> '1.0',
	'rte_author'		=> 'Aaron Gustafson',
	'rte_author_url'	=> 'http://easy-designs.net/',
	'rte_description'	=> 'Triggers the RTE to make the selected blocks into ordered list items',
	'rte_definition'	=> Ordered_list_rte::definition()
);

Class Ordered_list_rte {
	
	private $EE;
	
	public $globals = array();
	public $scripts	= array();
	public $styles	= null;
	
	/** -------------------------------------
	/**  Constructor
	/** -------------------------------------*/
	function __construct()
	{
		// Make a local reference of the ExpressionEngine super object
		$this->EE =& get_instance();
		
		// Anything else we need?
		$this->EE->lang->loadfile('rte');
		$this->globals = array(
			'rte.ordered_list.add'		=> lang('make_ul'),
			'rte.ordered_list.remove'	=> lang('remove_ul')
		);
	}

	function definition()
	{
		ob_start(); ?>
		
		toolbar.addButton({
	    	name:			'ordered_list',
			label:			EE.rte.ordered_list.add,
			'toggle-text':	EE.rte.ordered_list.remove,
	    	handler: function( $ed ){
		 		return $ed.toggleOrderedList();
			},
			query: function( $editor ){
				return $editor.queryCommandState('insertOrderedList');
			}
	    });
		
<?php	$buffer = ob_get_contents();
		ob_end_clean(); 
		return $buffer;
	}

} // END Ordered_list_rte

/* End of file rte.ordered_list.php */
/* Location: ./system/expressionengine/rte_tools/ordered_list/rte.ordered_list.php */