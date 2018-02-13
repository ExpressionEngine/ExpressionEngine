/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

$(document).ready(function () {

	var publishForm = $(".form-standard > form");
	var ajaxRequest;
	var debounceTimeout;

	function debounceAjax(func, wait) {
	    var result;

        var context = this, args = arguments;
        var later = function() {
          debounceTimeout = null;
          result = func.apply(context, args);
        };

        clearTimeout(debounceTimeout);
		if (ajaxRequest) ajaxRequest.abort();

		debounceTimeout = setTimeout(later, wait);
		return result;
	};

	if (EE.publish.title_focus == true) {
		publishForm.find("input[name=title]").focus();
	}

	if (EE.publish.which == 'new') {
		publishForm.find("input[name=title]").bind("keyup blur", function() {
			publishForm.find('input[name=title]')
				.ee_url_title(publishForm.find('input[name=url_title]'));
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
							publishForm.find('ul.tabs').after(result.success);
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

	var fetchPreview = function() {
		var iframe      = $('iframe.live-preview__frame')[0],
		    preview_url = $(iframe).data('url');

		ajaxRequest = $.ajax({
			type: "POST",
			dataType: 'html',
			url: preview_url,
			data: publishForm.serialize(),
			complete: function(xhr) {
				iframe.contentDocument.open();
				iframe.contentDocument.write(xhr.responseText);
				iframe.contentDocument.close();
				ajaxRequest = null;
			},
		});
	};

	$('body').on('entry:preview', function (event, wait) {
		if (wait == undefined) {
			wait = 0;
		}

		if ($('.app-modal--live-preview:visible').length) {
			debounceAjax(fetchPreview, wait);
		}
	});

	$('body').on('click', 'button[rel="live-preview"]', function(e) {
		var container = $('.app-modal--live-preview .form-standard');
		fetchPreview();

		container.append($(publishForm));

		$(container).on('interact', 'input, textarea', function(e) {
			$('body').trigger('entry:preview', [225]);
		});

		$(container).on('change', 'input[type=checkbox], input[type=radio], input[type=hidden], select', function(e) {
			$('body').trigger('entry:preview');
		});

		$(container).on('click', 'a.toggle-btn', function(e) {
			$('body').trigger('entry:preview');
		});

		$('button[rel="live-preview"]').hide();
	});

	$('.app-modal--live-preview').on('modal:close', function(e) {
		$('[data-publish]').append($('.app-modal--live-preview .form-standard > form'));
		$('button[rel="live-preview"]').show();
	});

	if (window.location.search.includes('&preview=y')) {
		$('button[rel="live-preview"]').click();
	}

});
