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
 File: rte.blockquote.php
-----------------------------------------------------
 Purpose: Blockquote RTE Tool
=====================================================

*/

Class Blockquote_rte {
	
	public $info = array(
		'name'			=> 'Blockquote',
		'version'		=> '1.0',
		'description'	=> 'Triggers the RTE to block quote or un-quote the selected block of text',
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
			'rte.blockquote'	=> array(
				'add'		=> lang('make_blockquote'),
				'remove'	=> lang('remove_blockquote')
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
			name: 			'blockquote',
			label:			EE.rte.blockquote.add,
			'toggle-text': 	EE.rte.blockquote.remove,
			handler: 	function( $ed ){
				return $ed.toggleIndentation();
			},
			query: function( $editor, $btn ){
				var
				selection	= window.getSelection(),
				hasRange	= !! selection.rangeCount,
				el			= selection.anchorNode;

				if ( hasRange )
				{
					while ( el.nodeType != "1" )
					{
						el = el.parentNode;
						
						if (el == null)
						{
							break;
						}
					}
				}
				
				$blockquote	= $(el).parents('blockquote');
				return  !! $blockquote.length;
			}
		});
		
<?php	$buffer = ob_get_contents();
		ob_end_clean(); 
		return $buffer;
	}

} // END Blockquote_rte

/* End of file rte.blockquote.php */
/* Location: ./system/expressionengine/rte_tools/blockquote/rte.blockquote.php */