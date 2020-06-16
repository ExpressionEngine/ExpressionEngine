(function($) {


/**
 * Display
 */
var display = function($element)
{
	var textarea = $element.find('.wygwam textarea[data-config]');
    textarea.attr('id', textarea.attr('name').replace(/[\[\]]+/g, '_'));
    new Wygwam(textarea);
}

ContentElements.bind('wygwam', 'display', display);

/**
 * Before Sort
 */
ContentElements.bind('wygwam', 'beforeSort', function($element){
	var $textarea = $('textarea', $element),
		$iframe = $('iframe:first', $element);

	// has CKEditor been initialized?
	if (! $iframe.hasClass('wygwam')) {

		// Make a clone of the editor DOM
		$element.data('ckeClone', $element.children('.cke').clone());

		// save the latest HTML value to the textarea
		var id = $textarea.attr('id'),
			editor = CKEDITOR.instances[id];

		editor.updateElement();

		// destroy the CKEDITOR.editor instance
		editor.destroy();

		// make it look like nothing happened
		$textarea.hide();
		$element.data('ckeClone').appendTo($element);
	}
});

/**
 * After Sort
 */
ContentElements.bind('wygwam', 'afterSort', function($element) {
	if ($element.data('ckeClone'))
	{
		$element.data('ckeClone').remove();
	}
	display($element);
});


})(jQuery);
