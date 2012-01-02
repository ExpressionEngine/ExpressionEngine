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
 File: rte.view_source.php
-----------------------------------------------------
 Purpose: View Source RTE Tool
=====================================================

*/

$rte_tool_info = array(
	'rte_name'			=> 'View Source',
	'rte_version'		=> '1.0',
	'rte_author'		=> 'Aaron Gustafson',
	'rte_author_url'	=> 'http://easy-designs.net/',
	'rte_description'	=> 'Triggers the RTE to switch to and from view source mode',
	'rte_definition'	=> Rte_view_source::definition()
);

Class View_source_rte {

	/** -------------------------------------
	/**  Constructor
	/** -------------------------------------*/
	function View_source_rte()
	{
		// Make a local reference of the ExpressionEngine super object
		$this->EE =& get_instance();
		
		// any other initialization stuff can go here and can be made available in the definition
	}

	function definition()
	{
		ob_start(); ?>
		toolbar.addButton({
			name:	'switch',
			label:	'HTML',
			'toggle-text': 'Content',
			handler: function( $editor, e ){
				$editor.toggleHTML( e );
			}
		});
<?php	$buffer = ob_get_contents();
		ob_end_clean(); 
		return $buffer;
	}

} // END Rte_view_source

/* End of file rte.view_source.php */
/* Location: ./system/expressionengine/rte/rte.view_source.php */