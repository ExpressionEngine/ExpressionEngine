/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
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

		$('body').on('keypress change', writeable, function(){publishForm.trigger("entry:startAutosave")});
		$('body').on('change', changeable, function(){publishForm.trigger("entry:startAutosave")});
	}


	// -------------------------------------------------------------------
	// Live Preview
	// -------------------------------------------------------------------

	var tokenRefreshRequest = null;
	var tokenRefreshSkew = 30;

	var base64UrlDecode = function(data) {
		if (!data) return null;
		data = data.replace(/-/g, '+').replace(/_/g, '/');
		var padding = data.length % 4;
		if (padding) {
			data += '='.repeat(4 - padding);
		}
		try {
			return atob(data);
		} catch (e) {
			return null;
		}
	};

	var getTokenExp = function(token) {
		if (!token) return null;
		var parts = token.split('.');
		if (parts.length < 2) return null;
		var payload = base64UrlDecode(parts[0]);
		if (!payload) return null;
		try {
			var claims = JSON.parse(payload);
			return claims.exp || null;
		} catch (e) {
			return null;
		}
	};

	var shouldRefreshToken = function(token) {
		if (!token) return true;
		var exp = getTokenExp(token);
		if (!exp) return true;
		var now = Math.floor(Date.now() / 1000);
		return (exp - now) <= tokenRefreshSkew;
	};

	var getPreviewParams = function(preview_url) {
		var queryIndex = preview_url.indexOf('?');
		if (queryIndex === -1) {
			return {};
		}
		var query = preview_url.substring(queryIndex + 1);
		var params = {};
		query.split('&').forEach(function(pair) {
			if (!pair) return;
			var parts = pair.split('=');
			var key = decodeURIComponent(parts[0] || '');
			var value = decodeURIComponent(parts.slice(1).join('=') || '');
			if (key) {
				params[key] = value;
			}
		});
		return params;
	};

	var ensurePreviewToken = function(iframe, preview_url, token_url, done, forceRefresh) {
		var token = $(iframe).data('token');
		if (!token_url || (!forceRefresh && !shouldRefreshToken(token))) {
			done();
			return;
		}

		if (tokenRefreshRequest) {
			tokenRefreshRequest.always(done);
			return;
		}

		var params = getPreviewParams(preview_url);
		var data = {};
		if (params.from) {
			data.from = params.from;
		}
		if (params.return) {
			data.return = params.return;
		}

		tokenRefreshRequest = $.ajax({
			type: "POST",
			dataType: 'json',
			url: token_url,
			data: data,
		}).done(function(response) {
			if (response && response.token) {
				$(iframe).data('token', response.token);
			}
		}).always(function() {
			tokenRefreshRequest = null;
			done();
		});
	};

	var sendPreviewRequest = function(retrying, forceRefresh) {
		var iframe         = $('iframe.live-preview__frame')[0],
		    preview_url    = $(iframe).data('url'),
		    token_url      = $(iframe).data('token-url'),
		    preview_token  = $(iframe).data('token');

		// Show that the preview is refreshing
		$('.live-preview__preview-loader').addClass('loaded');

		// Save the current scroll position before updating the iframe
		var savedScrollPosition = null;
		try {
			if (iframe.contentWindow && iframe.contentDocument && iframe.contentDocument.documentElement) {
				// Try to get scroll position from contentWindow.scrollY (modern) or contentDocument.documentElement.scrollTop (fallback)
				savedScrollPosition = iframe.contentWindow.scrollY || 
				                      iframe.contentWindow.pageYOffset || 
				                      iframe.contentDocument.documentElement.scrollTop || 
				                      iframe.contentDocument.body.scrollTop || 
				                      0;
			}
		} catch (e) {
			// If we can't access the iframe content (cross-origin or not loaded), ignore
			savedScrollPosition = null;
		}

		ensurePreviewToken(iframe, preview_url, token_url, function() {
			preview_token = $(iframe).data('token');

			ajaxRequest = $.ajax({
				type: "POST",
				dataType: 'html',
				url: preview_url,
				crossDomain: true,
				beforeSend: function(request) {
					request.setRequestHeader("Access-Control-Allow-Origin", window.location.origin);
					if (preview_token) {
						request.setRequestHeader("Authorization", "Bearer " + preview_token);
					}
				},
				data: publishForm.serialize(),
				complete: function(xhr) {
					if (xhr.status === 403 && !retrying && token_url) {
						sendPreviewRequest(true, true);
						return;
					}
					if (xhr.responseText !== undefined) {
						iframe.contentDocument.open();
					
					// Inject scroll preservation script into the HTML for Firefox compatibility
					// This prevents Firefox from resetting scroll position during document close
					var htmlContent = xhr.responseText;
					if (savedScrollPosition !== null && savedScrollPosition > 0) {
						// Inject a script that preserves scroll position immediately when document loads
						var scrollScript = '<script>(function(){var saved=' + savedScrollPosition + ';function restore(){window.scrollTo(0,saved);document.documentElement.scrollTop=saved;document.body.scrollTop=saved;}if(document.readyState==="complete"){restore();}else{window.addEventListener("load",restore);document.addEventListener("DOMContentLoaded",restore);}requestAnimationFrame(restore);setTimeout(restore,0);setTimeout(restore,10);})();</script>';
						// Insert before closing body tag, or at end if no body tag
						if (htmlContent.indexOf('</body>') !== -1) {
							htmlContent = htmlContent.replace('</body>', scrollScript + '</body>');
						} else {
							htmlContent = htmlContent + scrollScript;
						}
					}
					
					iframe.contentDocument.write(htmlContent);
					
					// Restore scroll position BEFORE close() to prevent Firefox flicker
					// Firefox resets scroll position during close(), so we need to be more aggressive
					if (savedScrollPosition !== null && savedScrollPosition > 0) {
						// Function to restore scroll position
						var restoreScroll = function() {
							try {
								if (iframe.contentWindow) {
									iframe.contentWindow.scrollTo(0, savedScrollPosition);
									// Also try setting directly on document elements for Firefox
									if (iframe.contentDocument) {
										if (iframe.contentDocument.documentElement) {
											iframe.contentDocument.documentElement.scrollTop = savedScrollPosition;
										}
										if (iframe.contentDocument.body) {
											iframe.contentDocument.body.scrollTop = savedScrollPosition;
										}
									}
								}
							} catch (e) {
								// If we can't access the iframe, ignore
							}
						};

						// Try to restore BEFORE close() - this helps prevent Firefox flicker
						restoreScroll();
					}
					
					// Close the document
					iframe.contentDocument.close();

					// Restore scroll position after content is loaded
					if (savedScrollPosition !== null && savedScrollPosition > 0) {
						// Use requestAnimationFrame for smoother, frame-synced restoration
						// This is especially important for Firefox which handles iframe updates differently
						var restoreOnFrame = function() {
							restoreScroll();
							requestAnimationFrame(function() {
								restoreScroll();
								requestAnimationFrame(function() {
									restoreScroll();
								});
							});
						};
						
						// Immediate restoration attempts
						requestAnimationFrame(restoreOnFrame);
						
						// Also use timeouts as fallback for different rendering speeds
						setTimeout(restoreScroll, 0);   // Immediate attempt
						setTimeout(restoreScroll, 10);   // Very quick retry
						setTimeout(restoreScroll, 50);   // Quick retry for fast rendering
						setTimeout(restoreScroll, 100);  // Medium retry
						setTimeout(restoreScroll, 200);  // Final retry for slower rendering
					}
				}
					// Hide the refreshing indicator
					$('.live-preview__preview-loader').removeClass('loaded');
					ajaxRequest = null;
				},
			});
		}, forceRefresh);
	};

	var fetchPreview = function() {
		sendPreviewRequest(false, false);
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

		$('button[rel="live-preview"]').removeAttr('style').show();
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
