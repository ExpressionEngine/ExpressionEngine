/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

var Updater = {

	init: function ()
	{
		this._lastStep = $('.updating .updater-step-work').text();
		this._updaterInPlace = false;
		var that = this;

		$('.toggle').on('click', function(e) {
			e.preventDefault();
			var toggleIs = $(this).attr('rel');
			$('.'+toggleIs).toggle();
		});

		$('a[rel=rollback]').on('click', function(e) {
			e.preventDefault();
			that.runStep('rollback');
		});

		$('body').on('click', 'a[data-post-url]', function(event) {
			event.preventDefault();

			var form = $('<form/>', {
				action: $(this).data('postUrl'),
				method: 'post'
			});
			form.append($('<input/>', {
				name: 'csrf_token',
				value: EE.CSRF_TOKEN
			}));
			form.appendTo('body').submit();
		});

		this._bindSubscribe();
	},

	runStep: function(step) {
		if (step === undefined) {
			return;
		}

		$('.updating').removeClass('hidden');
		$('.updater-stopped').addClass('hidden');

		var that = this,
			action = EE.BASE + '&C=updater&M=run&step='+step;

		$.ajax({
			type: 'POST',
			url: action,
			dataType: 'json',
			headers: { 'X-CSRF-TOKEN': EE.CSRF_TOKEN },
			success: function(result) {
				if (result.messageType == 'success') {
					if (result.nextStep !== undefined && result.nextStep !== false) {
						that._updateStatus(result.message);
						that.runStep(result.nextStep);

						// If the updater is in place, we'll consider all errors 'issues'
						if ( ! that._updaterInPlace && result.nextStep == 'updateFiles') {
							that._updaterInPlace = true;
						}
					} else if ($('.updater-subscribe').length) {
						that._showSubscribe();
					} else {
						that._redirectToCp();
					}
				} else {
					that._showError(result);
				}
			},
			error: function(data) {
				error = data.responseJSON;
				if (error === undefined) {
					try {
						error = JSON.parse(data.responseText);
					} catch(err) {
						error = {
							messageType: 'error',
							message: data.responseText,
							trace: []
						};
					}
				}
				that._showError(error);
			}
		});
	},

	_bindSubscribe: function() {
		var that = this;

		$('body').on('submit', '#updater-subscribe-form', function(e) {
			e.preventDefault();
			that._submitSubscribe($(this));
		});

		$('body').on('click', '.js-updater-subscribe-skip', function(e) {
			e.preventDefault();
			that._redirectToCp();
		});
	},

	_updateStatus: function(message) {
		var progress_list = $('.updater-steps'),
			work_class = 'updater-step-work',
			pass_class = 'updater-step-pass',
			current_item = $('.'+work_class, progress_list);

		// If no message or is blank, don't update
		if (current_item.text().indexOf(message) !== -1 || message == '') {
			return;
		}

		// Mark previous item as finished
		current_item.removeClass(work_class)
			.addClass(pass_class)
			.find('span')
			.remove();

		// Create new item
		var new_item = $('<li/>', { class: work_class }).html(message + '<span>...</span>');

		this._lastStep = message;

		progress_list.append(new_item);
	},

	_showError: function(error, severity) {

		$('.updating').addClass('hidden');

		var issue_box = $('.updater-stopped'),
			severity = this._updaterInPlace ? 'issue' : 'warn',
			trace_link = $('.updater-fade', issue_box),
			trace_container = $('.updater-stack-trace', issue_box),
			trace_exists = error.trace !== undefined && error.trace.length > 0,
			message = error.message.replace(/(?:\r\n|\r|\n)/g, '<br />');

		issue_box.addClass(severity)
			.removeClass('hidden')
			.find('.alert-notice p')
			.html(message);

		$('p[class$=-choices]').addClass('hidden');
		$('p.'+severity+'-choices').removeClass('hidden');

		if (trace_exists) {
			var list = $('<ul/>');
			for (var i = 0; i < error.trace.length; i++) {
				list.append(
					$('<li/>').html(error.trace[i])
				);
			}
			trace_container.append(list);
		}

		trace_link.toggleClass('hidden', ! trace_exists);
		trace_container.toggleClass('hidden', ! trace_exists);

		$('.stopped', issue_box).html(EE.lang.we_stopped_on.replace('%s', this._lastStep));
	},

	_showSubscribe: function() {
		var panel = $('.updater-subscribe');

		$('.updating').addClass('hidden');
		$('.updater-stopped').addClass('hidden');
		panel.removeClass('hidden');
		$('.js-updater-subscribe-error', panel).addClass('hidden');
		$('#updater-subscribe-email').trigger('focus');
	},

	_showSubscribeError: function($form, message) {
		var errorBox = $('.js-updater-subscribe-error', $form);

		$('.js-updater-subscribe-error-text', errorBox).text(message);
		errorBox.removeClass('hidden');
	},

	_submitSubscribe: function($form) {
		var that = this,
			email = $.trim($('#updater-subscribe-email').val()),
			invalidMessage = $form.data('error-invalid-email'),
			submitErrorMessage = $form.data('error-submit'),
			marketingOptIn = $('#updater-subscribe-marketing').is(':checked') ? 'y' : 'n',
			emailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email),
			submitButton = $('button[type=submit]', $form);

		if (! emailValid) {
			return that._showSubscribeError($form, invalidMessage);
		}

		submitButton.prop('disabled', true).addClass('button--disabled');
		$('.js-updater-subscribe-error', $form).addClass('hidden');

		$.ajax({
			type: 'POST',
			url: EE.BASE + '&C=updater&M=subscribe',
			dataType: 'json',
			headers: { 'X-CSRF-TOKEN': EE.CSRF_TOKEN },
			data: {
				email: email,
				notifications_opt_in: 'y',
				marketing_opt_in: marketingOptIn
			},
			success: function(result) {
				if (result.messageType == 'success') {
					that._redirectToCp();
				} else {
					that._showSubscribeError($form, result.message || submitErrorMessage);
				}
			},
			error: function() {
				that._showSubscribeError($form, submitErrorMessage);
			},
			complete: function() {
				submitButton.prop('disabled', false).removeClass('button--disabled');
			}
		});
	},

	_redirectToCp: function() {
		window.location = EE.BASE;
	},

	_showSuccess: function() {
		$('.box').addClass('hidden');
		$('.panel').addClass('hidden');
		$('.success').removeClass('hidden');
	}
}
