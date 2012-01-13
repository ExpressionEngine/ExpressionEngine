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
 File: rte.unordered_list.php
-----------------------------------------------------
 Purpose: Unordered List RTE Tool
=====================================================

*/

$rte_tool_info = array(
	'rte_name'			=> 'Unordered List',
	'rte_version'		=> '1.0',
	'rte_author'		=> 'Aaron Gustafson',
	'rte_author_url'	=> 'http://easy-designs.net/',
	'rte_description'	=> 'Triggers the RTE to make the selected blocks into unordered list items',
	'rte_definition'	=> Unordered_list_rte::definition()
);

Class Unordered_list_rte {
	
	private $EE;
	
	/** -------------------------------------
	/**  Constructor
	/** -------------------------------------*/
	function __construct()
	{
		// Make a local reference of the ExpressionEngine super object
		$this->EE =& get_instance();
		
		// Anything else we need?
	}

	function definition()
	{
		ob_start(); ?>
		
		toolbar.addButton({
			label: "unordered_list",
			handler: function( $ed ){
				return $ed.toggleUnorderedList();
			},
			query: function( $editor ){
				return $editor.queryCommandState('insertUnorderedList');
			}
		});
		
<?php	$buffer = ob_get_contents();
		ob_end_clean(); 
		return $buffer;
	}

} // END Unordered_list_rte

/* End of file rte.unordered_list.php */
/* Location: ./system/expressionengine/rte_tools/unordered_list/rte.unordered_list.php */