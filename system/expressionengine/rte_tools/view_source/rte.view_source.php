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
	'rte_definition'	=> View_source_rte::definition()
);

Class View_source_rte {
	
	private $EE;
	
	/** -------------------------------------
	/**  Constructor
	/** -------------------------------------*/
	function __construct()
	{
		// Make a local reference of the ExpressionEngine super object
		$this->EE =& get_instance();
		
		// Make sure resize is added
		$this->EE->load->library('cp');
		$this->EE->cp->add_js_script(array('plugin' => 'ba-resize'));
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
		
		function syncSizes()
		{
			var $this = $(this);
			if ( $this.is('.WysiHat-editor') &&
				 $this.is(':visible') )
			{
				$this.data('field')
					.width($this.outerWidth())
					.height($this.outerHeight());
			}
			else if ( $this.is('.rte') &&
					  $this.is(':visible') )
			{
				$this.data('editor')
					.width($this.outerWidth())
					.height($this.outerHeight());
			}
			
			//$this.parent('.holder').find('.WysiHat-editor-toolbar')
			//	.width($this.outerWidth());
		}
		$editor.add($field)
			.bind('resize',syncSizes);
		
<?php	$buffer = ob_get_contents();
		ob_end_clean(); 
		return $buffer;
	}

} // END View_source_rte

/* End of file rte.view_source.php */
/* Location: ./system/expressionengine/rte_tools/view_source/rte.view_source.php */