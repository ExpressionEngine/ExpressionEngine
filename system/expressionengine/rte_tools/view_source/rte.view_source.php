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
 File: rte.view_source.php
-----------------------------------------------------
 Purpose: View Source RTE Tool
=====================================================

*/

$rte_tool_info = array(
	'rte_tool_name'				=> 'View Source',
	'rte_tool_version'			=> '1.0',
	'rte_tool_author'			=> 'Aaron Gustafson',
	'rte_tool_author_url'		=> 'http://easy-designs.net/',
	'rte_tool_description'		=> 'Triggers the RTE to switch to and from view source mode',
	'rte_tool_definition'		=> View_source_rte::definition()
);

Class View_source_rte {
	
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
			'rte.view_source'	=> array(
				'code'		=> lang('view_code'),
				'content'	=> lang('view_content')
			)
		);
	}
	
	/** -------------------------------------
	/**  Libraries we need loaded
	/** -------------------------------------*/
	function libraries()
	{
		return array(
			'plugin' => 'ba-resize'
		);
	}
	
	/** -------------------------------------
	/**  RTE Tool Definition
	/** -------------------------------------*/
	function definition()
	{
		ob_start(); ?>
		
		toolbar.addButton({
			name:			'view_source',
			label:			EE.rte.view_source.code,
			'toggle-text':	EE.rte.view_source.content,
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
		}
		$editor.add($field)
			.bind('resize',syncSizes);
		$editor.resize();
		
<?php	$buffer = ob_get_contents();
		ob_end_clean(); 
		return $buffer;
	}

} // END View_source_rte

/* End of file rte.view_source.php */
/* Location: ./system/expressionengine/rte_tools/view_source/rte.view_source.php */