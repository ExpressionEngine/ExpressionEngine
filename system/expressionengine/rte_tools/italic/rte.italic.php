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
 File: rte.italic.php
-----------------------------------------------------
 Purpose: Italic RTE Tool
=====================================================

*/

Class Italic_rte {

	public $info = array(
		'name'			=> 'Italic',
		'version'		=> '1.0',
		'description'	=> 'Italicizes and de-italicizes text',
		'cp_only'		=> 'n'
	);
	
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
			'rte.italics'	=> array(
				'add'		=> lang('make_italics'),
				'remove'	=> lang('remove_italics')
			)
		);
	}
	
	/** -------------------------------------
	/**  RTE Tool Definition
	/** -------------------------------------*/
	function definition()
	{
		ob_start(); ?>
		
		toolbar.addButton({
			name:			'italic',
			label:			EE.rte.italics.add,
			'toggle-text':	EE.rte.italics.remove
		});
		
<?php	$buffer = ob_get_contents();
		ob_end_clean(); 
		return $buffer;
	}

} // END Italic_rte

/* End of file rte.italic.php */
/* Location: ./system/expressionengine/rte_tools/italic/rte.italic.php */