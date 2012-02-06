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
 File: rte.underline.php
-----------------------------------------------------
 Purpose: Underline RTE Tool
=====================================================

*/

Class Underline_rte {

	public $info = array(
		'name'			=> 'Underline',
		'version'		=> '1.0',
		'description'	=> 'Underlines and de-underlines text',
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
			'rte.underline'	=> array(
				'add'		=> lang('make_underline'),
				'remove'	=> lang('remove_underline')
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