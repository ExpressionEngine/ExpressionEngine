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
 File: rte.image.php
-----------------------------------------------------
 Purpose: Image RTE Tool
=====================================================

*/

$rte_tool_info = array(
	'rte_name'			=> 'Image',
	'rte_version'		=> '1.0',
	'rte_author'		=> 'Aaron Gustafson',
	'rte_author_url'	=> 'http://easy-designs.net/',
	'rte_description'	=> 'Inserts and manages image alignment in the RTE',
	'rte_definition'	=> Image_rte::definition()
);

Class Image_rte {
	
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
			name: 'image',
	        label: "❏",
	        handler: function( $ed ){
				$ed.insertImage( prompt('What image do you want to insert?') );
			}
	    });
		
<?php	$buffer = ob_get_contents();
		ob_end_clean(); 
		return $buffer;
	}

} // END Image_rte

/* End of file rte.image.php */
/* Location: ./system/expressionengine/rte_tools/image/rte.image.php */