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
 File: rte.blockquote.php
-----------------------------------------------------
 Purpose: Blockquote RTE Tool
=====================================================

*/

$rte_tool_info = array(
	'rte_name'			=> 'Blockquote',
	'rte_version'		=> '1.0',
	'rte_author'		=> 'Aaron Gustafson',
	'rte_author_url'	=> 'http://easy-designs.net/',
	'rte_description'	=> 'Triggers the RTE to block quote or un-quote the selected block of text',
	'rte_definition'	=> Blockquote_rte::definition()
);

Class Blockquote_rte {
	
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
			name: 		'blockquote',
			label:		 "“",
			handler: 	function( $ed ){
				return $ed.toggleIndentation();
			}
		});
		
<?php	$buffer = ob_get_contents();
		ob_end_clean(); 
		return $buffer;
	}

} // END Blockquote_rte

/* End of file rte.blockquote.php */
/* Location: ./system/expressionengine/rte_tools/blockquote/rte.blockquote.php */