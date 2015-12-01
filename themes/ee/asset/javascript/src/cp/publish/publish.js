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

	// Auto-assign category parents if configured to do so
	if (EE.publish.auto_assign_cat_parents == 'y') {
		$('input[name^="categories"]:checkbox').on('click', function(){

			// If we're unchecking, make sure its children are also unchecked
			if ( ! $(this).is(':checked')) {
				$(this).parents('li')
					.first()
					.find('input:checkbox')
					.prop('checked', false)
					.trigger('change');
			}

			// If we're checking, check its parents too
			if ($(this).is(':checked')) {
				$(this).parents('li')
					.find('> label input:checkbox')
					.prop('checked', true)
					.trigger('change');
			}
		});
	}

	// Category modal
	$('body').on('click', 'a[rel=modal-category-form]', function (e) {
		var modal_link = $(this),
			modal_name = modal_link.attr('rel'),
			modal = $('.' + modal_name),
			category_form_url = EE.publish.add_category.URL.replace('###', $(this).data('catGroup'));

		// Clear out modal from last request
		$('div.box', modal).html('');

		// Are we editing a category? Create a different form URL
		if ($(this).data('catId')) {
			category_form_url = EE.publish.edit_category.URL.replace('###', $(this).data('catGroup') + '/' + $(this).data('catId'));
		}

		$.ajax({
			type: "GET",
			url: category_form_url,
			dataType: 'html',
			success: function (data) {
				load_category_modal_data(modal, data, modal_link);
			}
		})
	});

	// Category deletion
	$('body').on('click', 'a[rel=modal-confirm-cat-remove]', function (e) {
		var modal = $('.' + $(this).attr('rel')),
			modal_link = $(this);

		// Add the name of the category we're deleting to the modal
		$('.checklist', modal)
			.html('')
			.append('<li>' + $(this).data('confirm') + '</li>');
		// Set the category ID to send to the categories deletion handler
		$('input[name="categories[]"]', modal).val($(this).data('catId'));

		$('form', modal).off('submit').on('submit', function() {

			$.ajax({
				type: 'POST',
				url: this.action,
				data: $(this).serialize(),
				dataType: 'json',
				success: function(result) {
					if (result.messageType == 'success') {
						modal.trigger('modal:close');
						modal_link.parents('fieldset').find('.setting-field').html(result.body);
					}
				}
			});

			return false;
		});

		e.preventDefault();
	});

	function load_category_modal_data(modal, data, modal_link) {
		$('div.box', modal).html(data);

		EE.cp.formValidation.init(modal);

		$('input[name=cat_name]', modal).bind('keyup keydown', function() {
			$(this).ee_url_title('input[name=cat_url_title]');
		});

		$('form', modal).off('submit').on('submit', function() {

			$.ajax({
				type: 'POST',
				url: this.action,
				data: $(this).add('input[name="categories[]"]').serialize()+'&save_modal=yes',
				dataType: 'json',

				success: function(result) {
					if (result.messageType == 'success') {
						modal.trigger('modal:close');
						modal_link.parents('fieldset').find('.setting-field').html(result.body);
					} else {
						load_category_modal_data(modal, result.body, modal_link);
					}
				}
			});

			return false;
		});
	}

});
