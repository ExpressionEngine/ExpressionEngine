/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.4
 * @filesource
 */

// Make sure we can create these methods without issues
var EE = EE || {};
EE.publish = EE.publish || {};
EE.publish.file_edit = EE.publish.file_edit || {};

(function($) {
	// Changes tabs in the modal
	EE.publish.file_edit.change_tabs = function() {
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
	
	EE.publish.file_edit.change_tabs();
	
	// Hides and shows image tools, so only one tool is showing at a time
	EE.publish.file_edit.image_tool_select = function() {
		$('#image_tools input[name=image_tool]').click(function(event) {
			// Hide existing exposed image tools
			$(this).parent().parent().siblings().find('div').slideUp();
			
			// Show image tool
			$(this).parent().siblings('div').slideDown();
			
			// TODO: Change a hidden field so we know which image tool to use
		});
	};
	
	EE.publish.file_edit.image_tool_select();
})(jQuery);