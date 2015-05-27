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
	$('*[data-conditional-modal]').click(function (e) {
		var data_element = $(this).data('conditional-modal');
		var ajax_url = $(this).data('confirm-ajax');
		var confirm_text = $(this).data('confirm-text');
		var confirm_input = $(this).data('confirm-input');
		var conditional_element = $('*[data-' + data_element + ']').eq(0);

		if ($(conditional_element).prop($(conditional_element).data(data_element))) {
			// First adjust the checklist
			var modalIs = '.' + $(conditional_element).attr('rel');
			$(modalIs + " .checklist").html(''); // Reset it

			if (typeof confirm_text != 'undefined') {
				$(modalIs + " .checklist").append('<li>' + confirm_text + '</li>');
			}

			var checked = $(this).parents('form').find('td input:checked, li input:checked');

			if (checked.length < 6) {
				checked.each(function() {
					$(modalIs + " .checklist").append('<li>' + $(this).attr('data-confirm') + '</li>');
				});
			} else {
				$(modalIs + " .checklist").append('<li>' + EE.lang.remove_confirm.replace('###', checked.length) + '</li>');
			}

			// Add hidden <input> elements
			checked.each(function() {
				$(modalIs + " .checklist li:last").append('<input type="hidden" name="' + $(this).attr('name') + '" value="' + $(this).val() + '">');
			});

			if (typeof confirm_input != 'undefined') {
				$("input[name='" + confirm_input + "']").each(function() {
					$(modalIs + " .checklist li:last").append('<input type="hidden" name="' + $(this).attr('name') + '" value="' + $(this).val() + '">');
				});
			}

			$(modalIs + " .checklist li:last").addClass('last');

			if (typeof ajax_url != 'undefined') {
				$.post(ajax_url, $(modalIs + " form").serialize(), function(data) {
					$(modalIs + " .ajax").html(data);
				});
			}

			var heightIs = $(document).height();

			$('.overlay').fadeIn('slow').css('height',heightIs);
			$('.modal-wrap' + modalIs).fadeIn('slow');
			e.preventDefault();
			$('#top').animate({ scrollTop: 0 }, 100);
		}
	})
});
