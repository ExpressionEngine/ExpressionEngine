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
 File: rte.ordered_list.php
-----------------------------------------------------
 Purpose: Ordered List RTE Tool
=====================================================

*/

Class Ordered_list_rte {
	
	public $info = array(
		'name'			=> 'Ordered List',
		'version'		=> '1.0',
		'description'	=> 'Triggers the RTE to make the selected blocks into ordered list items',
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
			'rte.ordered_list'	=> array(
				'add'		=> lang('make_ol'),
				'remove'	=> lang('remove_ol')
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