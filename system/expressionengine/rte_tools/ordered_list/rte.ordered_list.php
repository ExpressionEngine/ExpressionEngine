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

$rte_tool_info = array(
	'rte_tool_name'				=> 'Ordered List',
	'rte_tool_version'			=> '1.0',
	'rte_tool_author'			=> 'Aaron Gustafson',
	'rte_tool_author_url'		=> 'http://easy-designs.net/',
	'rte_tool_description'		=> 'Triggers the RTE to make the selected blocks into ordered list items',
	'rte_tool_definition'		=> Ordered_list_rte::definition()
);

Class Ordered_list_rte {
	
	private $EE;
	
	# should this be shown on the frontend?
	public	$frontend = 'y';
	
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
			'rte.unordered_list'	=> array(
				'add'		=> lang('make_ul'),
				'remove'	=> lang('remove_ul')
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