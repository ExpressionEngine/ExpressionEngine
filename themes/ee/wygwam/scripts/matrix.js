(function($) {


Wygwam.matrixColConfigs = {};


$.fn.ffMatrix.onDisplayCell.wygwam = function(cell, FFM) {

	var $textarea = $('textarea', cell),
		id = $textarea.attr('id'),
		config = Wygwam.matrixColConfigs[id],
		randId = id+'_'+Math.floor(Math.random()*100000000);

	if (config) {
		$textarea.attr('id', randId);
		new Wygwam(randId, config[0], config[1]);
	}
};

if (typeof $.fn.ffMatrix.onBeforeSortRow != 'undefined') {

	$.fn.ffMatrix.onBeforeSortRow.wygwam = function(cell, FFM) {
		var $textarea = $('textarea', cell),
			source = $('iframe:first', cell)[0].contentDocument.body.innerHTML;
		$textarea.val(source);
	};

	$.fn.ffMatrix.onSortRow.wygwam = function(cell, FFM) {
		$textarea = $('textarea', cell);
		$(cell).empty().append($textarea);
		var id = $textarea.attr('id');
		$textarea.attr('id', id.substr(0, id.length-9));
		$.fn.ffMatrix.onDisplayCell.wygwam(cell, FFM);
	}
}


})(jQuery);
