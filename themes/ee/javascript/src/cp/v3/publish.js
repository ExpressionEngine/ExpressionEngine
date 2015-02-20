/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

$(document).ready(function () {

	if (EE.publish.title_focus == true) {
		$("div.publish form input[name=title]").focus();
	}

	if (EE.publish.which == 'new') {
		$("div.publish form input[name=title]").bind("keyup blur", function() {
			$('div.publish form input[name=title]').ee_url_title($('div.publish form input[name=url_title]'));
		});
	}

	var autosave_entry,
		start_autosave;

	if (EE.publish.autosave && EE.publish.autosave.interval) {
		var autosaving = false;

		start_autosave = function() {
			if (autosaving) {
				return;
			}

			autosaving = true;
			setTimeout(autosave_entry, 1000 * EE.publish.autosave.interval); // 1000 milliseconds per second
		};

		autosave_entry = function() {
			var form = $("div.publish form");

			$.ajax({
				type: "POST",
				dataType: 'json',
				url: EE.publish.autosave.URL,
				data: form.serialize(),
				success: function(result) {
					form.find('div.alert.inline.warn').remove();

					if (result.error) {
						console.log(result.error);
					}
					else if (result.success) {
						form.prepend(result.success);
					}
					else {
						console.log('Autosave Failed');
					}

					autosaving = false;
				}
			});
		};

		// Start autosave when something changes
		var writeable = $('textarea, input').not(':password,:checkbox,:radio,:submit,:button,:hidden'),
			changeable = $('select, :checkbox, :radio, :file');

		writeable.bind('keypress change', start_autosave);
		changeable.bind('change', start_autosave);
	}

	// Load an auto-saved entry
	$('div.auto-save').on('click', 'li a', function(e) {
		$.ajax({
			type: "POST",
			dataType: 'json',
			url: EE.publish.restore.URL,
			data: {id: $(this).data('autosave-id')},
			success: function(result) {
				for (var property in result) {
					if (result.hasOwnProperty(property)) {
						var attrName = property;

						if (Array.isArray(result[property])) {
							attrName = property + "[]";
						}

						$('form [name="' + attrName + '"]').val(result[property]);
					}
				}
			}
		})
		e.preventDefault();
	});

});