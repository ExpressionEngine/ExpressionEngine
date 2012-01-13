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
 File: rte.underline.php
-----------------------------------------------------
 Purpose: Underline RTE Tool
=====================================================

*/

$rte_tool_info = array(
	'rte_name'			=> 'Underline',
	'rte_version'		=> '1.0',
	'rte_author'		=> 'Aaron Gustafson',
	'rte_author_url'	=> 'http://easy-designs.net/',
	'rte_description'	=> 'Underlines and de-underlines text',
	'rte_definition'	=> Underline_rte::definition()
);

Class Underline_rte {

	/** -------------------------------------
	/**  Constructor
	/** -------------------------------------*/
	function __construct()
	{
		// Make a local reference of the ExpressionEngine super object
		$this->EE =& get_instance();
		
		// any other initialization stuff can go here and can be made available in the definition
	}

	function definition()
	{
		ob_start(); ?>
		
		toolbar.addButton({ name: 'underline', label: "Underline" });
		
<?php	$buffer = ob_get_contents();
		ob_end_clean(); 
		return $buffer;
	}

} // END Underline_rte

/* End of file rte.underline.php */
/* Location: ./system/expressionengine/rte_tools/underline/rte.underline.php */