/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

"use strict";

(function ($) {
	$(document).ready(function () {

		$("input[name='avatar_picker']").each(function(){
			if ( ! $(this).is(':checked')) {
				$(this).parent().next().hide();
			}
		});

		$('input[name="avatar_picker"]').click(function(){
			if ($(this).is(':checked'))
			{
				$(this).parent().next().show();
				$("input[name='avatar_picker']").each(function(){
					if ( ! $(this).is(':checked')) {
						$(this).parent().next().hide();
					}
				});
			}
		});

		$('li.remove a').click(function (e) {
			$(this).closest('figure').find('input[type="hidden"]').val('');
			$(this).closest('fieldset').hide();
			e.preventDefault();
		});

		$('.avatarPicker').FilePicker({
			ajax: false,
			filters: false,
			callback: function(data, picker) {
				if (data instanceof jQuery)
				{
					data = data.find('img');
					picker.modal.find('.m-close').click();
					picker.input_value.val(data.attr('alt'));
					picker.input_img.html("<img src='" + data.attr('src') + "' />");
					picker.input_img.parents('fieldset').show();
				}
				else
				{
					picker.modal.find('.m-close').click();
					picker.input_value.val(data.file_id);
					picker.input_name.html(data.file_name);
					picker.input_img.html("<img src='" + data.path + "' />");
					picker.input_img.parents('fieldset').show();
				}
			}
		});

	});
})(jQuery);
