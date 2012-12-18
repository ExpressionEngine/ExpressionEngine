/**
 * Do NOT, I repeat do NOT, use this as a template for your buttons.
 *
 * This is terrible.
 */

(function() {

var $image_button,
	$parent,
	$editor,
	$field,
	dragging = false,
	image_finalize;

function setupImageTool($editor, $image_button) {

	var EE_rte_image	= EE.rte.image,
		$file_browser	= null,
		$figure_overlay = $('<div id="rte_image_figure_overlay" class="WysiHat-ui-control"><p></p></div>')
			.hide()
			.appendTo($editor.parents('.WysiHat-container')),
		$curr_figure	= null,
		saved_range		= null;
	

	$editor.on('mousedown', function() {
		dragging = true;
	}).on('mouseup', function() {
		dragging = false;
	});

	// Upon form submission, convert file upload paths back to
	// {filedir_n} format for storage in the database
	$editor
		.parents('form')
			.submit(function(){
				var folders = EE_rte_image.folders;
				$('.WysiHat-field').each(function(){
					var	$field	= $(this),
						val		= $field.val(),
						path, path_re;
					for ( path in folders )
					{
						path_re = new RegExp(path, 'g');
						val = val.replace( path_re, folders[path] );
					}
					$field.val(val);
				});
			});
	
	$image_button.click(function(){
		// make sure we have a ref to the file browser
		if ( ! $file_browser) {
			$file_browser = $('#file_browser');
		}

		// Keep the current range around until choosing a file
		// is completed in case the browser's selection changes
		// in the mean time
		sel = window.getSelection();
		saved_range = sel.getRangeAt(0);
	});
	
	$parent.append('<input type="hidden" id="rte_image_' + $field.attr('name') + '"/>');
	$.ee_filebrowser.add_trigger(
		$image_button,
		'userfile_' + $field.attr('name'),
		function(image_object, file_field, editor_field)
		{
			if ( ! $editor.is(':focus'))
			{
				$editor.focus();
			}
			
			var	$img = $('<figure/>')
				.css('text-align','center')
				.append(
					$('<img alt=""/>')
					.attr('src', image_object.thumb.replace(/_thumbs\//, ''))
				);
			
			if ((caption_text = prompt(EE_rte_image.caption_text, '')))
			{
				$img.append(
					$('<figcaption/>').text(caption_text)
				)
				.find('img').attr('alt', caption_text);
			}
			
			saved_range.insertNode( $img.get(0) );
			
			image_finalize();
			
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
				$('<button class="button align-left"><b>'+EE_rte_image.align_left+'</b></button>').click(function(e){
					e.preventDefault();
					alignFigureContent('left');
				})
			)
			.append(
				$('<button class="button align-center"><b>'+EE_rte_image.align_center+'</b></button>').click(function(e){
					e.preventDefault();
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
				$('<button class="button align-right"><b>'+EE_rte_image.align_right+'</b></button>').click(function(e){
					e.preventDefault();
					alignFigureContent('right');
				})
			)
			.append( $('<br/>') )
			.append(
				$('<button class="button wrap-left"><b>'+EE_rte_image.wrap_left+'</b></button>').click(function(e){
					e.preventDefault();
					var alignment = $curr_figure.css('text-align');
					$curr_figure
						.css('float','left')
						.data('floating',true);
					hideFigureOverlay();
				})
			)
			.append(
				$('<button class="button wrap-none"><b>'+EE_rte_image.wrap_none+'</b></button>').click(function(e){
					e.preventDefault();
					$curr_figure
						.css('float','none')
						.data('floating',false);
					hideFigureOverlay();
				})
			)
			.append(
				$('<button class="button wrap-right"><b>'+EE_rte_image.wrap_right+'</b></button>').click(function(e){
					e.preventDefault();
					var alignment = $curr_figure.css('text-align');
					$curr_figure
						.css('float','right')
						.data('floating',true);
					hideFigureOverlay();
				})
			)
			.append( $('<br/>') )
			.append(
				$('<button class="button remove"><b>'+EE_rte_image.remove+'</b></button>').click(function(e){
					e.preventDefault();
					$curr_figure.remove();
					hideFigureOverlay();
				})
			)
		.find('button').each(function(){
			var $this = $(this);
			$this.attr('title',$this.find('b').text());
		});
		
	$editor
		.delegate('figure img', 'mouseover', function(){
			if (dragging)
			{
				return;
			}

			var	$this	= $(this),
				offsets = $this.position();
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
			$figure_overlay.data('image', $this);
		});

	$figure_overlay.on('dblclick', function(e) {
		var $field = $figure_overlay.data('image'),
			sel = window.getSelection(),
			range = document.createRange();

		if ($field)
		{
			e.preventDefault();
			range.selectNode($field.get(0));
			sel.removeAllRanges();
			sel.addRange(range);
			hideFigureOverlay();
		}
	});
}

WysiHat.addButton('image', {
	label: EE.rte.image.add,
	init: function(name, $editor) {

		var filedirs	= EE.rte.image.filedirs,
			html		= $editor.html(),
			that		= this,
			path_re, path;

		// Firefox will return the left and right braces as entities,
		// we need to switch those back for replacement below
		html = html.replace('%7B', '{');
		html = html.replace('%7D', '}');

		for ( path in filedirs )
		{
			path_re = new RegExp(path, 'g');
			html = html.replace( path_re, filedirs[path] );
		}
		
		$field = $editor.data('field');
		$parent = $editor.parent();
		$editor.html(html);

		// blargh
		setTimeout(function() {
			setupImageTool($editor, that.$element);
		}, 50);

		return this.parent.init(name, $editor);
	},
	handler: function(state, finalize) {
		image_finalize = finalize;
		return false;
	}
});

})();