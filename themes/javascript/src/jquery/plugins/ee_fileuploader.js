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
			height: 300,
			resizable: false,
			position: ["center","center"],
			modal: true,
			draggable: true,
			title: "Upload File",
			autoOpen: false,
			zIndex: 99999,
			open: function() {
				var dir_id = $('#dir_choice').val(),
					source = file_uploader.find('iframe').attr('src'),
					source_position = source.search('&directory_id=');
					
				// Check to see if the source already has directory_id and remove it
				if (source_position > 0) {
					source = source.substring(0, source_position);
				};
				
				// Set a GET variable on the iframe to automatically select the correct directory
				file_uploader.find('iframe').attr('src', source + '&directory_id=' + dir_id);
			},
			close: function() {
				$.ee_filebrowser.reload_directory($('#dir_choice').val());
			}
		});
		
		$('#fileChooser #upload_form input').live('click', function(event) {
			file_uploader.dialog('open');
		});
	};

})(jQuery);
