(function($) {


Teepee.matrixColConfigs = {};


$.fn.ffMatrix.onDisplayCell.teepee = function(cell, FFM) {

	var $textarea = $('textarea', cell),
		id = $textarea.attr('id'),
		config = Teepee.matrixColConfigs[id],
		randId = id+'_'+Math.floor(Math.random()*100000000);

	if (config) {
		$textarea.attr('id', randId);
		new Teepee(randId, config[0], config[1]);
	}
};

if (typeof $.fn.ffMatrix.onBeforeSortRow != 'undefined') {

	$.fn.ffMatrix.onBeforeSortRow.teepee = function(cell, FFM) {
		var $textarea = $('textarea', cell),
			source = $('iframe:first', cell)[0].contentDocument.body.innerHTML;
		$textarea.val(source);
	};

	$.fn.ffMatrix.onSortRow.teepee = function(cell, FFM) {
		$textarea = $('textarea', cell);
		$(cell).empty().append($textarea);
		var id = $textarea.attr('id');
		$textarea.attr('id', id.substr(0, id.length-9));
		$.fn.ffMatrix.onDisplayCell.teepee(cell, FFM);
	}
}


})(jQuery);
