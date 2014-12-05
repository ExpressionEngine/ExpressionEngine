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
		var conditional_element = $('*[data-' + data_element + ']').eq(0);

		if ($(conditional_element).prop($(conditional_element).data(data_element))) {
			// First adjust the checklist
			var modalIs = '.' + $(conditional_element).attr('rel');

			$(modalIs + " .checklist").html(''); // Reset it
			if ($('td input:checked').length < 6) {
				$('td input:checked').each(function() {
					$(modalIs + " .checklist").append('<li>' + $(this).attr('data-confirm') + '</li>');
				});
			} else {
				$(modalIs + " .checklist").append('<li>' + EE.lang.remove_confirm.replace('###', $('td input:checked').length) + '</li>');
			}
			// Add hidden <input> elements
			$('td input:checked').each(function() {
				$(modalIs + " .checklist li:last").append('<input type="hidden" name="' + $(this).attr('name') + '" value="' + $(this).val() + '">');
			});
			$(modalIs + " .checklist li:last").addClass('last');

			// Second build and show the modal
			var contents = $('.' + $(conditional_element).attr('rel')).html();
			modalIs = '.modal-wrap' + modalIs;

			$('section.wrap').after('<div class="modal-wrap ' + $(conditional_element).attr('rel') + '"> <div class="modal"> <div class="col-group"> <div class="col w-16"> <a class="m-close" href="#"></a> <div class="box"> </div> </div> </div> </div> </div>');

			$(modalIs + ' div.box').append(contents);
			$(modalIs + ' .m-close').click(function (e) {
				$(modalIs).detach();
				$('.overlay').fadeOut('slow');
				$('.modal-wrap').fadeOut('slow');
				e.preventDefault();
			});

			var heightIs = $(document).height();

			$('.overlay').fadeIn('slow').css('height',heightIs);
			$(modalIs).fadeIn('slow');
			e.preventDefault();
			$('#top').animate({ scrollTop: 0 }, 100);
		}
	})
});