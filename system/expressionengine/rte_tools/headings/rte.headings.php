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
 File: rte.headings.php
-----------------------------------------------------
 Purpose: Headings RTE Tool
=====================================================

*/

$rte_tool_info = array(
	'rte_name'			=> 'Headings',
	'rte_version'		=> '1.0',
	'rte_author'		=> 'Aaron Gustafson',
	'rte_author_url'	=> 'http://easy-designs.net/',
	'rte_description'	=> 'Adds or swaps heading levels in the RTE. Can also revert text to a paragraph.',
	'rte_definition'	=> Headings_rte::definition()
);

Class Headings_rte {
	
	private $EE;
	
	/** -------------------------------------
	/**  Constructor
	/** -------------------------------------*/
	function __construct()
	{
		// Make a local reference of the ExpressionEngine super object
		$this->EE =& get_instance();
		
		// Anything else we need?
	}

	function definition()
	{
		ob_start(); ?>
		
		var $formatting_selector = $('<select class="button picker"/>');
		
		$formatting_selector
			.append('<option value="p">Paragraph</option>')
			.append('<option value="h1">Heading 1</option>')
			.append('<option value="h2">Heading 2</option>')
			.append('<option value="h3">Heading 3</option>')
			.append('<option value="h4">Heading 4</option>')
			.append('<option value="h5">Heading 5</option>')
			.append('<option value="h6">Heading 6</option>')
			.change(function(){
				$editor.changeContentBlock( $(this).val() );
			})
			.appendTo( $parent.find('.WysiHat-editor-toolbar') );
		
		// update the selector as the user clicks around
		$editor.bind(
			'keyup mouseup',
			function(){
				var
				selection	= window.getSelection(),
				hasRange	= !! selection.rangeCount,
				el			= selection.anchorNode;

				if ( hasRange )
				{
					while ( el.nodeType != "1" )
					{
						el = el.parentNode;
					}
				}
				
				if ( $(el).is('p,h1,h2,h3,h4,h5,h6') )
				{
					$formatting_selector.val(el.nodeName.toLowerCase());
				}
			});
		
<?php	$buffer = ob_get_contents();
		ob_end_clean(); 
		return $buffer;
	}

} // END Headings_rte

/* End of file rte.headings.php */
/* Location: ./system/expressionengine/rte_tools/headings/rte.headings.php */