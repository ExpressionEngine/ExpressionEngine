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
 File: rte.link.php
-----------------------------------------------------
 Purpose: Link RTE Tool
=====================================================

*/

$rte_tool_info = array(
	'rte_name'			=> 'Link',
	'rte_version'		=> '1.0',
	'rte_author'		=> 'Aaron Gustafson',
	'rte_author_url'	=> 'http://easy-designs.net/',
	'rte_description'	=> 'Triggers the RTE to link the selected text',
	'rte_definition'	=> Link_rte::definition()
);

Class Link_rte {
	
	private $EE;
	
	/** -------------------------------------
	/**  Constructor
	/** -------------------------------------*/
	function __construct()
	{
		// Make a local reference of the ExpressionEngine super object
		$this->EE =& get_instance();
		
		// Anything else we need?
		$this->EE->load->library(array('cp','javascript'));
		$this->EE->javascript->set_global(array(
			'rte.link.add'						=> lang('make_link'),
			'rte.link_dialog.title'				=> lang('rte_link_preferences'),
			'rte.link_dialog.url_field_label'	=> lang('url'),
			'rte.link_dialog.title_field_label'	=> lang('title'),
			'rte.link_dialog.rel_field_label'	=> lang('relationship'),
			'rte.link_dialog.submit_button'		=> lang('submit'),
			'rte.link_dialog.selection_error'	=> lang('selection_error')
		));
		$this->EE->javascript->compile();
		$this->EE->cp->add_js_script(array(
			'ui'		=> 'dialog'
		));
		$this->EE->cp->add_to_head(
			'
			<style>
				#rte_link_dialog p { margin-bottom:10px; }
				#rte_link_dialog label { width: 25%; display: inline-block; }
				#rte_link_dialog input, #rte_link_dialog select { width: 70%; margin-left: 10px; }
				#rte_link_dialog .submit { margin-left: 30%; cursor: pointer; }
			</style>
			'
		);
	}

	function definition()
	{
		ob_start(); ?>
		
		var
		$link_dialog = $(
							'<div id="rte_link_dialog">' +
								'<p><label for="rte_link_url">' + EE.rte.link_dialog.url_field_label + '</label><input type="url" id="rte_link_url"/></p>' +
								'<p><label for="rte_link_title">' + EE.rte.link_dialog.title_field_label + '</label><input type="text" id="rte_link_title"/></p>' +
								//'<p><label for="rte_link_rel">' + EE.rte.link_dialog.rel_field_label + '</label>' +
								// '<select id="rte_link_rel"></select></p>' +
								'<p><button class="submit" type="submit">' + EE.rte.link_dialog.submit_button + '</button></p>' +
							 '</div>'
						),
		link_ranges	= [];
		
		$link_dialog
			.appendTo('body')
			.dialog({
				width: 400,
				height: 150,
				resizable: false,
				position: ["center","center"],
				modal: true,
				draggable: true,
				title: EE.rte.link_dialog.title,
				autoOpen: false,
				zIndex: 99999,
				open: function(e, ui) {
					$editor.restoreRanges( link_ranges );
					
					var
					selection	= window.getSelection(),
					el			= selection.anchorNode;
					if ( el )
					{
						while ( el.nodeType != 1 )
						{
							el = el.parentNode;
						}
						el = $(el);
						if ( el.is('a') )
						{
							$('#rte_link_url').val( el.attr('href') );
							$('#rte_link_title').val( el.attr('title') );
						}
					}
				},
				close: function(e, ui) {
					$editor.restoreRanges( link_ranges );
					$editor.linkSelection( $('#rte_link_url').val() );
					
					var
					selection	= window.getSelection(),
					el			= selection.anchorNode,
					title		= $('#rte_link_title').val();
					if ( el )
					{
						while ( el.nodeType != 1 )
						{
							el = el.parentNode;
						}
						el = $(el);
						if ( el.is('a') )
						{
							el.attr('title',title);
						}
					}
					
					$('#rte_link_url,#rte_link_title').val('');
					
					// trigger the update
					$editor.trigger( EE.rte.update_event );
				}
			})
			// setup the close on enter
			.delegate('input','keypress',function( e ){
				// enter
				if ( e.which == 13 )
				{
					$link_dialog.dialog('close');
				}
			 })
			// setup the submit button
			.find('.submit')
				.click(function(){
					$link_dialog.dialog("close");
				 });
		
		toolbar.addButton({
			name:	'link',
	        label:	EE.rte.link.add,
	        handler: function(){

				link_ranges	= $editor.getRanges();

				var
				selection	= window.getSelection(),
				linkable	= !! selection.rangeCount,
				el			= selection.anchorNode,
				range		= document.createRange();
				
				if ( linkable &&
					 el == selection.focusNode &&
					 selection.anchorOffset == selection.focusOffset )
				{
					linkable = false;
				}

				while ( el.nodeType != 1 )
				{
					el = el.parentNode;
				}
				
				if ( el.nodeName.toLowerCase() == 'a' )
				{
					linkable = true;
					// select the whole <a>
					range.selectNode( el )
					selection.removeAllRanges();
					selection.addRange( range );
					link_ranges	= $editor.getRanges();
				}
				
				if ( linkable )
				{
					$link_dialog.dialog("open");
				}
				else
				{
					alert( EE.rte.link_dialog.selection_error );
				}
			},
			query: function( $editor ){
				return $editor.queryCommandState('createLink');
			}
	    });
		
<?php	$buffer = ob_get_contents();
		ob_end_clean(); 
		return $buffer;
	}

} // END Link_rte

/* End of file rte.link.php */
/* Location: ./system/expressionengine/rte_tools/link/rte.link.php */