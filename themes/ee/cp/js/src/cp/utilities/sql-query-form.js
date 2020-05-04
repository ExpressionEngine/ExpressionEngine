
$(document).ready(function () {

	var textarea = document.querySelector('.js-sql-query-textarea')

	// Use CodeMirror for the query form
	var editor = CodeMirror.fromTextArea(textarea, {
		lineWrapping: true,
		lineNumbers: true,
		autoCloseBrackets: true,
		styleActiveLine: true,
		showCursorWhenSelecting: true,
		mode: "sql",
		smartIndent: false
	});

	window.insertIntoSQlQueryForm = function(query) {
		var doc = editor.getDoc()
		var cursor = doc.getCursor()
		doc.replaceRange(query, cursor)
	}
});
