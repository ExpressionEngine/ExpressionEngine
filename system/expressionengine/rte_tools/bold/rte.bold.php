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
 File: rte.bold.php
-----------------------------------------------------
 Purpose: Bold RTE Tool
=====================================================

*/

$rte_tool_info = array(
	'rte_name'			=> 'Bold',
	'rte_version'		=> '1.0',
	'rte_author'		=> 'Aaron Gustafson',
	'rte_author_url'	=> 'http://easy-designs.net/',
	'rte_description'	=> 'Bolds and un-bolds selected text',
	'rte_definition'	=> Bold_rte::definition()
);

Class Bold_rte {

	/** -------------------------------------
	/**  Constructor
	/** -------------------------------------*/
	function __construct()
	{
		// Make a local reference of the ExpressionEngine super object
		$this->EE =& get_instance();
		
		// any other initialization stuff can go here and can be made available in the definition
		$this->EE->load->library('javascript');
		$this->EE->javascript->set_global(array(
			'rte.bold.add'		=> lang('make_bold'),
			'rte.bold.remove'	=> lang('remove_bold')
		));
		$this->EE->javascript->compile();
	}

	function definition()
	{
		ob_start(); ?>
		
		toolbar.addButton({
			name: 			'bold',
			label: 			EE.rte.bold.add,
			'toggle-text':	EE.rte.bold.remove
		});
		
<?php	$buffer = ob_get_contents();
		ob_end_clean(); 
		return $buffer;
	}

} // END Bold_rte

/* End of file rte.bold.php */
/* Location: ./system/expressionengine/rte_tools/bold/rte.bold.php */