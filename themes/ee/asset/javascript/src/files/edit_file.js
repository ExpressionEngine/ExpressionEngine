/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

// Make sure we can create these methods without issues
var EE = EE || {};
EE.publish = EE.publish || {};
EE.publish.edit_file = EE.publish.edit_file || {};

(function($) {
	// Reset to default visible tabs
	EE.publish.edit_file.reset_tabs = function() {
		// Setup default visible tabs
		$('.panel-menu li').removeClass('current')
			.filter(':first').addClass('current');

		$('.panels > div').removeClass('current')
			.filter(':first').addClass('current');
	};

	// Changes tabs in the modal
	EE.publish.edit_file.change_tabs = function() {
		EE.publish.edit_file.reset_tabs();

		// Show the clicked tab
		$('.panel-menu li a').click(function(event) {
			var id = $(this).data('panel');

			// Change classes and hide old panels
			$('.panels').children().hide().removeClass('current')
				.filter('#' + id).show().addClass('current');

			// Change classes on tabs
			$(this).parent().addClass('current')
				.siblings().removeClass('current');

			event.preventDefault();
		});
	};

	EE.publish.edit_file.change_tabs();

	// Hides and shows image tools, so only one tool is showing at a time
	EE.publish.edit_file.image_tool_select = function() {
		$('#image_tools input[name=image_tool]').click(function(event) {
			// Hide existing exposed image tools
			$(this).parent().parent().siblings().find('div').slideUp();

			// Show image tool
			$(this).parent().siblings('div').slideDown();

			// Reset resize
			if ($(this).val() != 'resize') {
				$('#resize_height').val(EE.filemanager.image_height);
				$('#resize_width').val(EE.filemanager.image_width);
			}

			// Change the value of action hidden input
			$('input[name=action]').val($(this).val());
		});
	};

	EE.publish.edit_file.image_tool_select();

	// Submit listener doesn't work here, I assume due to iframe
	$('form#edit_file_metadata').resize_scale({
		"cancel_resize": 	'#cancel_resize',
		"default_height": 	EE.filemanager.image_height,
		"default_width": 	EE.filemanager.image_width
	});
})(jQuery);
