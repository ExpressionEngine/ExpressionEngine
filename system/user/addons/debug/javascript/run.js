var acc = $('#debug'),
	form = acc.find('form'),
	code = acc.find('textarea'),
	code_el = code.get(0),
	result = acc.find('.accessorySection').eq(1);

result.hide();

code.keydown(function (e) {
	if (e.keyCode == 9) { // tab
		if ('selectionStart' in code_el) {
			var newStart = code_el.selectionStart + "\t".length;

			code_el.value = code_el.value.substr(0, code_el.selectionStart) +
							"\t" +
							code_el.value.substr(code_el.selectionEnd, code_el.value.length);
			code_el.setSelectionRange(newStart, newStart);
		}
		else if (document.selection) {
			document.selection.createRange().text = "\t";
		}

		return false;
	}

	if (e.keyCode == 13 && (e.metaKey || e.ctrlKey))
	{
		form.triggerHandler('submit');
		return false;
	}
});

form.submit(function() {
	var url = this.action;
	result.fadeOut('fast');

	$.post(url, {code: code.val()}, function(res) {
		result.find('div').html(res);
		result.fadeIn('fast');
	});

	return false;
});