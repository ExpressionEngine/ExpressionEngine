/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

$(document).ready(function () {

	var publishForm = $("div.publish form");

	if (EE.publish.title_focus == true) {
		$("div.publish form input[name=title]").focus();
	}

	if (EE.publish.which == 'new') {
		$("div.publish form input[name=title]").bind("keyup blur", function() {
			$('div.publish form input[name=title]').ee_url_title($('div.publish form input[name=url_title]'));
		});
	}

	// Emoji
	if (EE.publish.smileys === true) {
		$('.format-options .toolbar .emoji a').click(function(e) {
			$(this).parents('.format-options').find('.emoji-wrap').slideToggle('fast');
			e.preventDefault();
		});
	}

	// Autosaving
	if (EE.publish.autosave && EE.publish.autosave.interval) {
		var autosaving = false;

		publishForm.on("entry:startAutosave", function() {
			publishForm.trigger("entry:autosave");

			if (autosaving) {
				return;
			}

			autosaving = true;
			setTimeout(function() {
				$.ajax({
					type: "POST",
					dataType: 'json',
					url: EE.publish.autosave.URL,
					data: publishForm.serialize(),
					success: function(result) {
						publishForm.find('div.alert.inline.warn').remove();

						if (result.error) {
							console.log(result.error);
						}
						else if (result.success) {
							publishForm.prepend(result.success);
						}
						else {
							console.log('Autosave Failed');
						}

						autosaving = false;
					}
				});
			}, 1000 * EE.publish.autosave.interval); // 1000 milliseconds per second
		});

		// Start autosave when something changes
		var writeable = $('textarea, input').not(':password,:checkbox,:radio,:submit,:button,:hidden'),
			changeable = $('select, :checkbox, :radio, :file');

		writeable.on('keypress change', function(){publishForm.trigger("entry:startAutosave")});
		changeable.on('change', function(){publishForm.trigger("entry:startAutosave")});
	}

	// Category modal
	$('a[rel=modal-add-category]').click(function (e) {
		var modal = $(this).attr('rel')
		$.ajax({
			type: "GET",
			url: EE.publish.add_category.URL.replace('###', $(this).data('catGroup')),
			dataType: 'html',
			success: function (data) {
				var modal_box = $("." + modal + " div.box");
				modal_box.html(data);

				EE.cp.formValidation.init(modal_box);
				EE.cp.categoryEdit.init(modal_box);
			}
		})
	});

});
