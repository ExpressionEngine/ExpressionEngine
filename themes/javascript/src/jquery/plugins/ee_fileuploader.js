/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

(function($) {
	
	var file_uploader;

	$.ee_fileuploader = function() {
		$.ee_filebrowser.endpoint_request('setup_upload', function(data) {
			file_uploader = $(data.uploader).appendTo(document.body);
			
			$(document).ready(function() {
				$.ee_fileuploader.build_dialog();
			});
		});
		
	};
	
	$.ee_fileuploader.build_dialog = function() {
		file_uploader.dialog({
			width: 600,
			height: 400,
			resizable: false,
			position: ["center","center"],
			modal: true,
			draggable: true,
			title: "Upload File",
			autoOpen: false,
			zIndex: 99999,
			open: function(event, ui) {
				var selected_directory = $('#dir_choice :selected').text();
				
				$(file_uploader).find('span.location').text(selected);
			}
		});
		
		$('#fileChooser #upload_form input').live('click', function(event) {
			file_uploader.dialog('open');
		});
	};

})(jQuery);
