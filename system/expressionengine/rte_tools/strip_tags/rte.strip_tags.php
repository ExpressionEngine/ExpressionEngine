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
 File: rte.strip_tags.php
-----------------------------------------------------
 Purpose: Strip Tags RTE Tool
=====================================================

*/

Class Strip_tags_rte {
	
	public $info = array(
		'name'			=> 'Strip Tags',
		'version'		=> '1.0',
		'description'	=> 'Triggers the RTE to strip all block and phrase-level formatting elements',
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
			'rte.strip_tags.label' => lang('strip_tags')
		);
	}
	
	/** -------------------------------------
	/**  RTE Tool Definition
	/** -------------------------------------*/
	function definition()
	{
		ob_start(); ?>
		
		toolbar.addButton({
			name:	"strip_tags",
			label:	EE.rte.strip_tags.label,
			handler: function( $ed ){
				$ed.stripFormattingElements();
				$ed.unformatContentBlock();
			}
		});
		
<?php	$buffer = ob_get_contents();
		ob_end_clean(); 
		return $buffer;
	}

} // END Strip_tags_rte

/* End of file rte.strip_tags.php */
/* Location: ./system/expressionengine/rte_tools/strip_tags/rte.strip_tags.php */