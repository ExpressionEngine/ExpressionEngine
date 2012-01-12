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
 File: rte.image.php
-----------------------------------------------------
 Purpose: Image RTE Tool
=====================================================

*/

$rte_tool_info = array(
	'rte_name'			=> 'Image',
	'rte_version'		=> '1.0',
	'rte_author'		=> 'Aaron Gustafson',
	'rte_author_url'	=> 'http://easy-designs.net/',
	'rte_description'	=> 'Inserts and manages image alignment in the RTE',
	'rte_definition'	=> Image_rte::definition()
);

Class Image_rte {
	
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
		$this->EE->javascript->set_global( 'rte.image.caption_text', lang('rte_image_caption') );
		$this->EE->javascript->set_global( 'rte.image.center_error', lang('rte_center_error') );
		$this->EE->cp->add_js_script(array(
			'plugin'	=> 'ee_filebrowser'
		));
		$this->EE->cp->add_to_head(
			'
			<style>
				.rte_image_caption { border-bottom: 1px solid #ccc; margin-bottom: 15px; padding-bottom: 15px; }
				#rte_image_caption { width:300px; margin-left: 10px; }
				#rte_image_figure_overlay { display:table; position: absolute; background: #999; background: rgba( 0, 0, 0, .5); }
				#rte_image_figure_overlay p { display: table-cell; vertical-align: middle; text-align:center; }
				#rte_image_figure_overlay .button { float: none; margin: 5px; }
				#rte_image_figure_overlay .delete { margin: 50px 0 0; }
			</style>
			'
		);
	}

	function definition()
	{
		ob_start(); ?>
		
		var
		$file_browser	= null,
		$caption		= $('<p class="rte_image_caption"><strong>' + EE.rte.image.caption_text + '</strong> <input type="text" id="rte_image_caption"/></p>'),
		$caption_field	= $caption.find('#rte_image_caption'),
		$figure_overlay = $('<div id="rte_image_figure_overlay" class="WysiHat-ui-control"><p></p></div>').hide().appendTo('body'),
		$curr_figure	= null,
		$image_button	= toolbar.addButton({
			name: 'image',
	        label: "Insert Image",
	        handler: function( $ed ){
				// nothing (we observe from elsewhere)
			}
	    });
		
		$image_button.click(function(){
			// make sure we have a ref to the file browser
			if ( ! $file_browser )
			{
				$file_browser = $('#file_browser');
			}
			$file_browser
				// switch the view
				.find('#view_type')
					.val('thumb')
					.change()
				// hide the view_type field to not allow it to change
					.parent()
						.hide()
						.end()
					.end()
				// append the caption field
				.find('#filterMenu')
					.prepend( $caption );
		});
		
		$parent.append('<input type="hidden" id="rte_image_' + $field.attr('name') + '"/>');
		$.ee_filebrowser.add_trigger(
			$image_button,
			'userfile_' + $field.attr('name'),
			function( image_object, file_field, editor_field ){
				
				var
				$img		= $('<figure/>').css('text-align','center'),
				selection	= window.getSelection(),
				range, $els;
				if ( selection.rangeCount )
				{
					range	= selection.getRangeAt(0);
					$els	= $editor.getRangeElements( range, WysiHat.Element.getBlocks().join(',') );
					range.setStartBefore( $els.get(0) );
					range.setEndBefore( $els.get(0) );
					range.collapse();
				}
				else
				{
					range = document.createRange();
					range.selectNode( $editor.get(0).firstChild );
				}
				
				$img.append(
					$('<img alt=""/>')
						.attr( 'src', image_object.thumb.replace( /_thumbs\//, '' ) )
						.attr( 'data-ee_img_path', "{filedir_" + image_object.upload_location_id + "}/" + image_object.file_name )
				);
				
				if ( $caption_field.val() != '' )
				{
					$img.append(
						$('<figcaption/>').text( $caption_field.val() )
					);
					$caption_field.val('');
				}
				$caption.remove();
				
				range.insertNode( $img.get(0) );
				
				$file_browser
					// switch the view back
					.find('#view_type')
						.val('list')
						.change()
						// show the footer again
						.parent()
							.show();
			}	
		);
		
		// figure overlay setup
		function hideFigureOverlay()
		{
			$figure_overlay.hide();
			$curr_figure = null;
		}
		function alignFigureContent( direction )
		{
			var css = {'text-align':direction};
			if ( $curr_figure.data('floating') )
			{
				css.float = direction;
			}
			$curr_figure.css(css);
			hideFigureOverlay();
		}
		$figure_overlay
			.mouseleave(hideFigureOverlay)
			.find('p')
				.append(
					$('<button class="button align-left">Align Left</button>').click(function(){ alignFigureContent('left'); })
				 )
				.append(
					$('<button class="button align-center">Align Center</button>').click(function(){
						if ( $curr_figure.data('floating') )
						{
							alert(EE.rte.image.center_error);
						}
						else
						{
							$curr_figure.css('text-align','center');
							hideFigureOverlay();
						}
					})
				 )
				.append(
					$('<button class="button align-right">Align Right</button>').click(function(){ alignFigureContent('right'); })
				 )
				.append( $('<br/>') )
				.append(
					$('<button class="button separate">Separate Text</button>').click(function(){
						$curr_figure
							.css('float','none')
							.data('floating',false);
						hideFigureOverlay();
					}) 
				 )
				.append(
					$('<button class="button wrap">Wrap Text</button>').click(function(){
						var alignment = $curr_figure.css('text-align');
						$curr_figure
							.css( 'float', ( alignment == 'right' ? 'right' : 'left' ) )
							.data('floating',true);
						hideFigureOverlay();
					})
				 )
				.append( $('<br/>') )
				.append(
					$('<button class="button delete">Delete Image</button>').click(function(){
						$curr_figure.remove();
						hideFigureOverlay();
					})
				 );
		$editor
			.delegate('figure','mouseover',function(){
				$curr_figure = $(this).closest('figure');
				$curr_figure.data( 'floating', ( $curr_figure.css('float') != 'none' ) );
				var offsets = $curr_figure.offset();
				$figure_overlay
					.css({
						display:	'table',
						left:		offsets.left,
						top:		offsets.top,
						height:		$curr_figure.outerHeight(),
						width:		$curr_figure.outerWidth()
					 });
			 });
		
<?php	$buffer = ob_get_contents();
		ob_end_clean(); 
		return $buffer;
	}

} // END Image_rte

/* End of file rte.image.php */
/* Location: ./system/expressionengine/rte_tools/image/rte.image.php */