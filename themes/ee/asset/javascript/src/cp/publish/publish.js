/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

$(document).ready(function () {

	var publishForm = $("[data-publish] > form");
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
		$('body').on('click', '.format-options .toolbar .emoji a', function(e) {
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
						var publishHeading = $('[data-publish] .form-btns-top h1');
						publishHeading.find('.app-badge').remove();

						if (result.error) {
							console.log(result.error);
						}
						else if (result.success) {
							publishHeading.append(result.success);
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
		var iframe         = $('iframe.live-preview__frame')[0],
		    preview_url    = $(iframe).data('url'),
			preview_banner = $('.live-preview > .app-notice---important');

		preview_banner.removeClass('app-notice---important').addClass('app-notice---loading');
		preview_banner.find('[data-loading]').removeClass('hidden');
		preview_banner.find('[data-unpublished]').addClass('hidden');
		preview_banner.find('.js-preview-wide').addClass('hidden');

		ajaxRequest = $.ajax({
			type: "POST",
			dataType: 'html',
			url: preview_url,
			data: publishForm.serialize(),
			complete: function(xhr) {
				if (xhr.responseText !== undefined) {
					iframe.contentDocument.open();
					iframe.contentDocument.write(xhr.responseText);
					iframe.contentDocument.close();

					preview_banner.removeClass('app-notice---loading').addClass('app-notice---important');
					preview_banner.find('[data-loading]').addClass('hidden');
					preview_banner.find('[data-unpublished]').removeClass('hidden');
					preview_banner.find('.js-preview-wide').removeClass('hidden');
				}
				ajaxRequest = null;
			},
		});
	};

	$(document).on('entry:preview', function (event, wait) {
		if (wait == undefined) {
			wait = 0;
		}

		if ($('.app-modal--live-preview:visible').length) {
			debounceAjax(fetchPreview, wait);
		}
	});

	$('body').on('click', 'button[rel="live-preview"]', function(e) {
		var container = $('.app-modal--live-preview .form-standard'),
		    iframe      = $('iframe.live-preview__frame')[0];

		iframe.contentDocument.open();
		iframe.contentDocument.write('');
		iframe.contentDocument.close();

		fetchPreview();

		container.append($(publishForm));

		$(container).on('interact', 'input, textarea', function(e) {
			$('body').trigger('entry:preview', [225]);
		});

		$(container).on('change', 'input[type=checkbox], input[type=radio], input[type=hidden], select', function(e) {
			$(document).trigger('entry:preview');
		});

		$(container).on('click', 'a.toggle-btn', function(e) {
			$(document).trigger('entry:preview');
		});

		$('button[rel="live-preview"]').hide();

		$(document).trigger('entry:preview-open')
	});

	$('.app-modal--live-preview').on('modal:close', function(e) {
		$('[data-publish]').append($('.app-modal--live-preview .form-standard > form'));
		$('button[rel="live-preview"]').show();
		$(document).trigger('entry:preview-close')
	});

	if (window.location.search.includes('&preview=y')) {
		setTimeout(function() {
			$('button[rel="live-preview"]').click();
		}, 100);
	}

	// =============
	// live preview width control
	// =============

	$('.js-preview-wide').on('click',function(){
		var txtIs = $(this).text();
		var closeTxtIs = $(this).attr('data-close');
		var openTxtIs = $(this).attr('data-open');

		$('.live-preview---open').toggleClass('live-preview--wide');
		$(this).text(txtIs == closeTxtIs ? openTxtIs : closeTxtIs);
	});

	var previewButtonStartedHidden = $('button[value="preview"]').hasClass('hidden');

	var showPreviewButton = function(e) {
		var pagesURI      = $('input[name="pages__pages_uri"]'),
		    pagesTemplate = $('input[name="pages__pages_template_id"]'),
		    button        = $('button[value="preview"]')
			show          = false;

		show = (pagesURI.val() != '' && (pagesTemplate.val() != '' || e.target.nodeName.toLowerCase() == 'label'));

		if (show) {
			button.removeClass('hidden');
		}

		if ( ! show && previewButtonStartedHidden) {
			button.addClass('hidden');
		}
	};

	$('input[name="pages__pages_uri"]').on('interact', showPreviewButton);
	$('div[data-input-value="pages__pages_template_id"] .field-inputs label').on('click', showPreviewButton);

	// Everything's probably ready, re-enable publish buttons
	$('[data-publish] .form-btns button:disabled').removeAttr('disabled');
});
