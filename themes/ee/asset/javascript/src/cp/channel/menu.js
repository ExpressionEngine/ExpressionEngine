/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

$(document).ready(function () {
	bindChannelSetImport()

	function bindChannelSetImport() {
		var fileInput = $('<input/>', {
			type: 'file',
			name: 'set_file'
		})

		var channelSetForm = $('<form/>', {
			method: 'post',
			action: EE.sets.importUrl,
			enctype: 'multipart/form-data',
			class: 'hidden'
		}).append($('<input/>', {
			type: 'hidden',
			name: 'csrf_token',
			value: EE.CSRF_TOKEN
		})).append(fileInput)

		fileInput.on('change', function(e) {
			channelSetForm.submit()
		})

		$('a[rel="import-channel"]').click(function(e) {
			e.preventDefault()
			fileInput.click()
		}).after(channelSetForm)
	}
});
