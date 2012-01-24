var
range			= null,
$file_browser	= null,
$caption		= $('<p class="rte_image_caption"><strong>' + EE.rte.image.caption_text + '</strong> <input type="text" id="rte_image_caption"/></p>'),
$caption_field	= $caption.find('#rte_image_caption'),
$figure_overlay = $('<div id="rte_image_figure_overlay" class="WysiHat-ui-control"><p></p></div>').hide().appendTo('body'),
$curr_figure	= null,
$image_button	= toolbar.addButton({
	name:		'image',
    label:		EE.rte.image.add,
	handler: function( $ed ){
		// nothing (we observe from elsewhere)
	}
});

function getTheRange(){
	var
	ranges		= $editor.getRanges(),
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

	if ( ! el || ! $editor.has( $(el) ).length )
	{
		hasRange = false;
	}

	if ( hasRange )
	{
		range	= selection.getRangeAt(0).cloneRange();
		el		= $editor.getRangeElements( range, WysiHat.Element.getBlocks().join(',') ).get(0);
		if ( $(el).is('li,dt,dd,td') )
		{
			range.setStart( el, 0 );
			range.setEnd( el, 0 );
		}
		else
		{
			range.setStartBefore( el );
			range.setEndBefore( el );
		}
		range.collapse(true);
	}
	else
	{
		range = document.createRange();
		range.selectNode( $editor.get(0).firstChild );
	}
}
$editor.mouseup(getTheRange);

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
	function( image_object, file_field, editor_field )
	{
		if ( ! range )
		{
			getTheRange();
		}
		
		var	$img = $('<figure/>').css('text-align','center');
		
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
		
		// trigger the update
		$editor.trigger( EE.rte.update_event );
		
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

	// trigger the update
	$editor.trigger( EE.rte.update_event );
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
			$('<button class="button align-left"><b>Align Left</b></button>').click(function(){ alignFigureContent('left'); })
		 )
		.append(
			$('<button class="button align-center"><b>Align Center</b></button>').click(function(){
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
			$('<button class="button align-right"><b>Align Right</b></button>').click(function(){ alignFigureContent('right'); })
		 )
		.append( $('<br/>') )
		.append(
			$('<button class="button separate"><b>Separate Text</b></button>').click(function(){
				$curr_figure
					.css('float','none')
					.data('floating',false);
				hideFigureOverlay();
			}) 
		 )
		.append(
			$('<button class="button wrap"><b>Wrap Text</b></button>').click(function(){
				var alignment = $curr_figure.css('text-align');
				$curr_figure
					.css( 'float', ( alignment == 'right' ? 'right' : 'left' ) )
					.data('floating',true);
				hideFigureOverlay();
			})
		 )
		.append( $('<br/>') ).append( $('<br/>') ).append( $('<br/>') )
		.append(
			$('<button class="button delete"><b>Delete Image</b></button>').click(function(){
				$curr_figure.remove();
				hideFigureOverlay();
			})
		 );
$editor
	.delegate('figure img','mouseover',function(){
		var
		$this	= $(this),
		offsets = $this.offset();
		$curr_figure = $(this).closest('figure');
		$curr_figure.data( 'floating', ( $curr_figure.css('float') != 'none' ) );
		$figure_overlay
			.css({
				display:	'table',
				left:		offsets.left,
				top:		offsets.top,
				height:		$this.outerHeight(),
				width:		$this.outerWidth()
			 });
	 });