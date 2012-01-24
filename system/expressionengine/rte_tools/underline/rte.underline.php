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
			'rte.underline.add'		=> lang('make_underline'),
			'rte.underline.remove'	=> lang('remove_underline')
		);
	}
	
	/** -------------------------------------
	/**  RTE Tool Definition
	/** -------------------------------------*/
	function definition()
	{
		ob_start(); ?>
		
		toolbar.addButton({
			name:			'underline',
			label:			EE.rte.underline.add,
			'toggle-text':	EE.rte.underline.remove
		});
		
<?php	$buffer = ob_get_contents();
		ob_end_clean(); 
		return $buffer;
	}

} // END Underline_rte

/* End of file rte.underline.php */
/* Location: ./system/expressionengine/rte_tools/underline/rte.underline.php */