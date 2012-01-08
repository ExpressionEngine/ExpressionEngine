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
 File: rte.headings.php
-----------------------------------------------------
 Purpose: Headings RTE Tool
=====================================================

*/

$rte_tool_info = array(
	'rte_name'			=> 'Headings',
	'rte_version'		=> '1.0',
	'rte_author'		=> 'Aaron Gustafson',
	'rte_author_url'	=> 'http://easy-designs.net/',
	'rte_description'	=> 'Adds or swaps heading levels in the RTE. Can also revert text to a paragraph.',
	'rte_definition'	=> Headings_rte::definition()
);

Class Headings_rte {
	
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
			label: "H1",
			handler: function( $ed ){
				$ed.changeContentBlock( 'h1' );
			}
		});
		toolbar.addButton({
			label: "H2",
			handler: function( $ed ){
				$ed.changeContentBlock( 'h2' );
			}
		});
		
<?php	$buffer = ob_get_contents();
		ob_end_clean(); 
		return $buffer;
	}

} // END Headings_rte

/* End of file rte.headings.php */
/* Location: ./system/expressionengine/rte_tools/headings/rte.headings.php */