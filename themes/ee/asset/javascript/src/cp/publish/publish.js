/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */
var isNavigatingAway = false;
function preventNavigateAway(e) {
	if (!isNavigatingAway && sessionStorage.getItem("preventNavigateAway") == 'true') {
		e.returnValue = EE.lang.confirm_exit;
		return EE.lang.confirm_exit;
	}
}
$(document).ready(function () {
	if(typeof isNavigatingAway === 'undefined') {
		var isNavigatingAway
	}

	isNavigatingAway = false;

	var publishForm = $("[data-publish] > form");
	var ajaxRequest;
	var debounceTimeout;
	try {
		sessionStorage.removeItem("preventNavigateAway");
	} catch (e) {}

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

	// check if command is clicked
	var cmdPressed = false;
	$(window).on('keydown', function(evt) {
		if (evt.which == 91 || evt.which == 17 || evt.which == 16) { // command/ctrl/shift
			cmdPressed = true;
		}
	}).on('keyup', function(evt) {
		if (evt.which == 91 || evt.which == 17 || evt.which == 16) { // command/ctrl/shift
			cmdPressed = false;
		}
	});
	//prevent navigating away
	$('body .ee-wrapper').on('click', 'a', function(e) {
		if (
			sessionStorage.getItem("preventNavigateAway") == 'true' &&
			$(this).attr('href') != null && 
			$(this).attr('href') != '' && 
			$(this).attr('href').indexOf('#') != 0  && 
			$(this).attr('href').indexOf('javascript:') != 0 &&
			$(this).attr('target') != '_blank' && 
			(!e.target.closest('[data-publish]') || (typeof(e.target.closest('[data-publish]').length)!=='undefined' && !e.target.closest('[data-publish]').length)) && 
			!cmdPressed
		) {
			isNavigatingAway = confirm(EE.lang.confirm_exit);
			return isNavigatingAway;
		}
	});

	//prevent navigating away using browser buttons
	
	window.addEventListener('beforeunload', preventNavigateAway);
	publishForm.on('submit', function(){
		window.removeEventListener('beforeunload', preventNavigateAway);
	});
	

	// Autosaving
	if (EE.publish.autosave && EE.publish.autosave.interval) {
		var autosaving = false;

		publishForm.on("entry:startAutosave", function() {
			try {
				sessionStorage.setItem("preventNavigateAway", true);
			} catch (e) {}
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
						var publishHeading = $('.ee-wrapper .panel-heading .title-bar h3');
						publishHeading.find('.app-badge').remove();

						if (result.error) {
							console.log(result.error);
						}
						else if (result.success) {
							publishHeading.append(result.success);
							sessionStorage.removeItem("preventNavigateAway");

							// Check if we're in an iframe, and emit appropriate events
							if(window.self !== window.top) {
								document.dispatchEvent(new CustomEvent('ee-pro-object-has-autosaved'));
							}
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
		var writeable = $('textarea, input, div.redactor-styles, div.ck-content').not(':password,:checkbox,:radio,:submit,:button,:hidden'),
			changeable = $('select, :checkbox, :radio, :file');

		writeable.on('keypress change', function(){publishForm.trigger("entry:startAutosave")});
		changeable.on('change', function(){publishForm.trigger("entry:startAutosave")});
	}


	// -------------------------------------------------------------------
	// Live Preview
	// -------------------------------------------------------------------

	var fetchPreview = function() {
		var iframe         = $('iframe.live-preview__frame')[0],
		    preview_url    = $(iframe).data('url');

		// Show that the preview is refreshing
		$('.live-preview__preview-loader').addClass('loaded');

		ajaxRequest = $.ajax({
			type: "POST",
			dataType: 'html',
			url: preview_url,
			crossDomain: true,
			beforeSend: function(request) {
				request.setRequestHeader("Access-Control-Allow-Origin", window.location.origin);
			},
			data: publishForm.serialize(),
			complete: function(xhr) {
				if (xhr.responseText !== undefined) {
					iframe.contentDocument.open();
					iframe.contentDocument.write(xhr.responseText);
					iframe.contentDocument.close();
				}
				// Hide the refreshing indicator
				$('.live-preview__preview-loader').removeClass('loaded');
				ajaxRequest = null;
			},
		});
	};

	$(document).on('entry:preview', function (event, wait) {
		if (wait == undefined) {
			wait = 0;
		}

		// Only update the live preview if it's open
		if ($('.live-preview-container:visible').length) {
			debounceAjax(fetchPreview, wait);
		}
	});

	$('body').on('click', 'button[rel="live-preview-setup"]', function(e) {
		e.preventDefault()

		$('body').prepend(EE.alert.lp_setup);

		return false;
	});

	$('body').on('click', 'button[rel="live-preview"]', function(e) {
		e.preventDefault()

		// Show the live preview modal
		$('.live-preview-container').show()
		setTimeout(function () { $('.live-preview').removeClass('live-preview--closed') }, 10);

		var container = $('.live-preview__form-content');
		var iframe      = $('iframe.live-preview__frame')[0];

		iframe.contentDocument.open();
		iframe.contentDocument.write('');
		iframe.contentDocument.close();

		fetchPreview();

		// Hide the save and preview buttons
		$('.tab-bar__right-buttons', publishForm).hide()

		// Move the publish form into the live preview container
		container.append($(publishForm));

		$(container).on('interact', 'input, textarea, div.redactor-styles, div.ck-content', function(e) {
			$('body').trigger('entry:preview', [225]);
		});

		$(container).on('change', 'input[type=checkbox], input[type=radio], input[type=hidden], select', function(e) {
			$(document).trigger('entry:preview');
		});

		$(container).on('click', 'button.toggle-btn', function(e) {
			$(document).trigger('entry:preview');
		});

		$(document).trigger('entry:preview-open')
	});


	$('.js-live-preview-save-button').on('click', function(e) {
		$('.js-live-preview-save-button').addClass('button--working');
		$('.live-preview__form-content form button[value="save"]').click()
	});

	$('.js-close-live-preview').on('click', function(e) {
		e.preventDefault()

		// Move the publish form back to the main page from the live preview modal
		$('[data-publish]').append($('.live-preview__form-content').children());

		// Show the save buttons
		$('[data-publish] .tab-bar__right-buttons').show()

		$('button[rel="live-preview"]').show();
		$(document).trigger('entry:preview-close')

		// Hide the live preview modal
		$('.live-preview').addClass('live-preview--closed')
		$('.live-preview-container').fadeOut(600)
	});

	// Open the preview automatically if the url wants us to
	if (window.location.search.includes('&preview=y')) {
		setTimeout(function() {
			$('button[rel="live-preview"]').click();
		}, 100);
	}


	// -------------------------------------------------------------------
	// live preview width control
	// -------------------------------------------------------------------

	function handleDrag(event, eventType, callback) {
        var doCallback = function (e) {
            callback(e)
            e.preventDefault()
        }

        var moveEventName = eventType == 'mouse' ? 'mousemove' : 'touchmove'
        var stopEventName = eventType == 'mouse' ? 'mouseup'   : 'touchend'

		window.addEventListener(moveEventName, doCallback)

		$('.live-preview__frame, .live-preview__form').css('pointer-events', 'none')

        window.addEventListener(stopEventName, function finish() {
            window.removeEventListener(moveEventName, doCallback)
			window.removeEventListener(stopEventName, finish)

			$('.live-preview__frame, .live-preview__form').css('pointer-events', 'all')
        })

        doCallback(event)
	}

	function onHandleDrag(e) {
		// Get the percentage x position of the mouse
		var xPos = e.clientX / $(document).width() * 100;
		// Prevent each side from getting too small
		xPos = Math.min(Math.max(xPos, 10), 98)

		// Set each sides width
		$('.live-preview__form').css('flex-basis', xPos + '%')
		$('.live-preview__preview').css('flex-basis', (100 - xPos) + '%')
	}

	$(".live-preview__divider").on('mousedown', function(e) { handleDrag(e, 'mouse', onHandleDrag) });
	$(".live-preview__divider").on('touchstart', function(e) { handleDrag(e, 'touch', onHandleDrag) });

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
