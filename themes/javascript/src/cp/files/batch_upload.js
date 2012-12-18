/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

$(document).ready(function () {
	$('select[name=upload_dirs]').change(function () {
		var val = $(this).val();
		
		$.ajax({
			url: EE.BASE + '&C=content_files&M=get_dir_cats',
			type: 'POST',
			data: {
				"XID": EE.XID,
				"upload_directory_id": val
			},
			success: function (res) {				
				if (res.error === true) {
					$('#file_cats').html('');
					return;
				}
				
				var html = '<fieldset class="holder">'+res+'</fieldset>';

				$('#file_cats').html(html);
				$('#file_cats').find('.edit_categories_link').hide();
			},
			error: function (res) {
				$('#file_cats').html('');
			}
		});
	});
});