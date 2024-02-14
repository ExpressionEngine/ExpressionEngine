/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

$(document).ready(function () {
	$('body').on('click', '*[data-conditional-modal]', function (e) {
		var data_element = $(this).data('conditional-modal');
		var ajax_url = $(this).data('confirm-ajax');
		var confirm_text = $(this).data('confirm-text');
		var confirm_input = $(this).data('confirm-input');
		var select = $('*[data-' + data_element + ']').closest('select').get(0);

		if (select) {
			var conditional_element = $(select.options[select.selectedIndex]);
		}

		var checked = $(this).parents('form').find('th input:checked, td input:checked, li input:checked, .file-grid__file input:checked, .file-grid__checkAll input:checked');

		checked = checked.filter(function(i, el) {
			return $(el).attr('value') !== undefined;
		});

		if (typeof($(conditional_element).data('action')) !== 'undefined') {
			e.preventDefault();
			if ($(conditional_element).data('action') == 'redirect') {
				if (checked.length && typeof(checked.first().data('redirect-url') !== 'undefined')) {
					window.location = checked.first().data('redirect-url');
				}
			} else if ($(conditional_element).data('action') == 'copy-link') {
				if (checked.length && typeof(checked.first().data('link') !== 'undefined')) {
					window.location = checked.first().data('link');
				}
			} else if ($(conditional_element).data('action') == 'download') {
				$('body').off('submit', '.container > .panel > .tbl-ctrls > form');
				$(this).parents('form').trigger('submit');
			}
			return;
		}

		if ($(conditional_element).data(data_element) &&
			$(conditional_element).prop($(conditional_element).data(data_element))) {
			e.preventDefault();
			// First adjust the checklist
			var modalIs = '.' + $(conditional_element).attr('rel');
			var modal = $(modalIs+', [rel='+$(conditional_element).attr('rel')+']')
			$(modalIs + " .checklist").html(''); // Reset it

			if (typeof confirm_text != 'undefined') {
				$(modalIs + " .checklist").append('<li>' + confirm_text + '</li>');
			}

			if (conditional_element.attr('rel') == 'modal-edit') {
				var entryIds = $.map(checked, function(el) {
					return $(el).val()
				})
				EE.cp.ModalForm.openForm({
					url: EE.publishEdit.sequenceEditFormUrl.replace('###', entryIds[0] + '&' + $.param({ entry_ids: entryIds })),
					full: true,
					iframe: true,
					success: function() {
						location.reload()
					},
					load: function (modal) {
						var title = modal.find('> iframe').last().contents().find('meta[name=modal-title]').attr('content')
						EE.cp.ModalForm.setTitle(title)
					}
				})
			}

			if (conditional_element.attr('rel') == 'modal-bulk-edit') {
				return EE.cp.BulkEdit.openForm(conditional_element.val(), checked)
			}


			if (checked.length < 6) {
				checked.each(function() {
					$(modalIs + " .checklist").append('<li>' + $(this).attr('data-confirm') + '</li>');
				});
			} else {
				$(modalIs + " .checklist").append('<li>' + EE.lang.remove_confirm.replace('###', checked.length) + '</li>');
			}

			// Add hidden <input> elements
			checked.each(function() {
				$(modalIs + " .checklist li:last").append(
					$('<input/>').attr({
						type: 'hidden',
						name: $(this).attr('name'),
						value: $(this).val()
					})
				);
			});

			if (typeof confirm_input != 'undefined') {
				$("input[name='" + confirm_input + "']").each(function() {
					$(modalIs + " .checklist li:last").append(
						$('<input/>').attr({
							type: 'hidden',
							name: $(this).attr('name'),
							value: $(this).val()
						})
					);
				});
			}

			$(modalIs + " .checklist li:last").addClass('last');

			if (typeof ajax_url != 'undefined') {
				$.post(ajax_url, $(modalIs + " form").serialize(), function(data) {
					$(modalIs + " .ajax").html(data);
					Dropdown.renderFields();

					if ($('div[data-select-react]').length) {
						SelectField.renderFields();
					}
				});
			}
			Dropdown.renderFields();
			modal.trigger('modal:open')
		}
	})

	$('body').on('click', 'a[rel="modal-confirm-delete-file"], a[rel="modal-confirm-move-file"], a[rel="modal-confirm-rename-file"], .member_manager-wrapper a[rel="modal-confirm-delete"], .member_manager-wrapper a[rel="modal-confirm-decline"]', function (e) {
		var ajax_url = $(this).data('confirm-ajax');
		var file_id = $(this).data('file-id');
		var file_name = $(this).parents('tr').find('input[type=checkbox]').attr('name');
		var checkboxInput = $(this).parents('tr').find('input[type=checkbox]').attr('data-confirm');
		e.preventDefault();

		// First adjust the checklist
		var modalIs = '.' + $(this).attr('rel');
		var modal = $(modalIs+', [rel='+$(this).attr('rel')+']')
		$(modalIs + " .checklist").html(''); // Reset it

		$(modalIs + " .checklist").append('<li>' + checkboxInput + '</li>');
		// Add hidden <input> elements
		$(modalIs + " .checklist li:last").append(
			$('<input/>').attr({
				type: 'hidden',
				name: file_name,
				value: file_id
			})
		);

		$(modalIs + " .checklist li:last").addClass('last');

		if (typeof ajax_url != 'undefined') {
			$.post(ajax_url, $(modalIs + " form").serialize(), function(data) {
				$(modalIs + " .ajax").html(data);
				Dropdown.renderFields();
			});
		}

		modal.trigger('modal:open')
	})

	$('.modal-confirm-delete-file form, .modal-confirm-move-file form, .modal-confirm-rename-file form').on('submit', function(e) {
		if( $(this).find('.ajax').length && $(this).find('button').hasClass('off') ) {
			$(this).find('.ajax .fieldset-invalid').show();
			e.preventDefault();
		}
	});
});

EE.cp.Modal = {

	/**
	 * Opens the confirm removal modal and does the form and view manipulation
	 * for the current item being deleted. Only supports one item at the moment.
	 *
	 * @param  {string}   actionUrl Form action URL for removal
	 * @param  {string}   label     Label of item being deleted
	 * @param  {mixed}    value     Value, typically an ID, of item being deleted
	 * @param  {Function} callback  Callback on deletion of item
	 * @return {void}
	 */
	openConfirmRemove: function(actionUrl, label, value, callback) {
		var modal = $('.modal-default-confirm-remove'),
			form = modal.find('form'),
			input = modal.find('input[name="content_id"]')

		// Add the name of the item we're deleting to the modal
		$('.checklist', modal)
			.html('')
			.append('<li>' + label + '</li>')

		// Reset buttons back to non-working state
		$('.form-btns .button', modal)
			.removeClass('work')
			.each(function(index, button) {
				if ($(button).data('submit-text')) {
					$(button).attr('value', $(button).data('submit-text'))
				}
			})

		input.val(value)
		form.attr('action', actionUrl)

		modal.trigger('modal:open')

		modal.find('form').submit(function() {
			$.post(this.action, $(this).serialize(), function(result) {
				modal.trigger('modal:close')
				callback(result)
			})

			return false
		})
	}
};
