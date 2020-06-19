(function($) {


Teepee.matrixColConfigs = {};


/**
 * Display
 */
var onDisplay = function(cell){

	var $textarea = $('textarea', cell.dom.$td),
		config = Teepee.matrixColConfigs[cell.col.id],
		id = cell.field.id+'_'+cell.row.id+'_'+cell.col.id+'_'+Math.floor(Math.random()*100000000);

	id = id.replace(/\[/, '_').replace(/\]/, '');

	$textarea.attr('id', id);

	new Teepee(id, config[0], config[1], cell);
};

Matrix.bind('teepee', 'display', onDisplay);

/**
 * Before Sort
 */
Matrix.bind('teepee', 'beforeSort', function(cell){
	var $textarea = $('textarea', cell.dom.$td),
		$iframe = $('iframe:first', cell.dom.$td);

	// has CKEditor been initialized?
	if (! $iframe.hasClass('teepee')) {

		// Make a clone of the editor DOM
		cell.dom.$ckeClone = cell.dom.$td.children('.cke').clone();

		// save the latest HTML value to the textarea
		var id = $textarea.attr('id'),
			editor = CKEDITOR.instances[id];

		editor.updateElement();

		// destroy the CKEDITOR.editor instance
		editor.destroy();

		// make it look like nothing happened
		$textarea.hide();
		cell.dom.$ckeClone.appendTo(cell.dom.$td);
	}
});

/**
 * After Sort
 */
Matrix.bind('teepee', 'afterSort', function(cell) {
	if (typeof cell.dom.$ckeClone != 'undefined')
	{
		cell.dom.$ckeClone.remove();
	}
	$('iframe:first', cell.dom.$td).remove();
	onDisplay(cell);
});


})(jQuery);
