/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

$(document).ready(function () {
	$('body').on('click', '*[data-conditional-modal]', function (e) {
		var data_element = $(this).data('conditional-modal');
		var ajax_url = $(this).data('confirm-ajax');
		var confirm_text = $(this).data('confirm-text');
		var confirm_input = $(this).data('confirm-input');
		var select = $('*[data-' + data_element + ']').closest('select').get(0)
		var conditional_element = $(select.options[select.selectedIndex])

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

			var checked = $(this).parents('form').find('th input:checked, td input:checked, li input:checked');

			checked = checked.filter(function(i, el) {
				return $(el).attr('value') !== undefined;
			});

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
					SelectField.renderFields();
				});
			}

			modal.trigger('modal:open')
		}
	})
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
		$('.form-btns .btn', modal)
			.removeClass('work')
			.each(function(index, button) {
				console.log(button)
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
