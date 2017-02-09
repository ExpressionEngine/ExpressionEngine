/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

var Updater = {

	runStep: function(step) {
		this._requestUpdate(step);
	},

	_requestUpdate: function(step) {
		if (step === undefined) {
			return;
		}

		var that = this,
			action = EE.BASE + '&C=updater&M=run&step='+step;

		$.ajax({
			type: 'POST',
			url: action,
			dataType: 'json',
			headers: { 'X-CSRF-TOKEN': EE.CSRF_TOKEN },
			success: function(result) {
				if (result.messageType == 'success') {
					if (result.nextStep !== undefined && result.nextStep !== false) {
						that._updateStatus(result.message);
						that._requestUpdate(result.nextStep);
					} else if (result.nextStep === false) {
						window.location = EE.BASE;
					}
				}
				if (result.messageType == 'error') {
					console.log(result.message);
				}
			},
			error: function(data) {
				console.log(data);
			}
		});
	},

	_updateStatus: function(message) {
		var progress_list = $('.updater-steps'),
			work_class = 'updater-step-work',
			pass_class = 'updater-step-pass',
			current_item = $('.'+work_class, progress_list);

		if (current_item.text().indexOf(message) !== -1 || message == '') {
			return;
		}

		// Mark previous item as finished
		current_item.removeClass(work_class)
			.addClass(pass_class)
			.find('span')
			.remove();

		// Create new item
		var new_item = $('<li/>', { class: work_class }).html(message + '<span>...</span>');

		progress_list.append(new_item);
	},

	_showSuccess: function() {
		$('.box').addClass('hidden');
		$('.box.success').removeClass('hidden');
	}
}
