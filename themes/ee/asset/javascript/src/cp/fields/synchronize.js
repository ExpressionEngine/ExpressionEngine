/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

EE.fieldManager = EE.fieldManager || {};
EE.fieldManager.chunk_size = 50;
EE.fieldManager.remainingCount = {
	total: EE.fieldManager.channel_entry_count,
	channels: [],
};

EE.fieldManager.entriesTodo = [];
EE.fieldManager.sync_running = 0;
EE.fieldManager.sync_errors = [];
EE.fieldManager.sync_timeout_id = 0;

$(document).ready(function() {
	EE.fieldManager.sync_listen();
});

EE.fieldManager.sync_listen = function() {
	$('.form-standard form .button').click(function(event) {
		event.preventDefault();
		// Disable sync button
		$('.button', $('.form-standard form')).each(function() {
			$(this).val($(this).data('work-text')).addClass('work').prop('disabled', true);
		});

		// Remove any existing alerts
		$('.app-notice--inline').remove();

		EE.fieldManager.entriesTodo = [];

		EE.fieldManager.groupedChannelEntryCounts.forEach(function(entryCounts) {
			// Set remainging for each channel
			EE.fieldManager.remainingCount.channels[entryCounts.channel_id] = entryCounts.entry_count;

			var offset = 0;

			while (offset < entryCounts.entry_count) {
				var limit = EE.fieldManager.chunk_size;

				if(entryCounts.entry_count - offset < EE.fieldManager.chunk_size) {
					limit = entryCounts.entry_count - offset;
				}

				EE.fieldManager.entriesTodo.push({
					channel_id: entryCounts.channel_id,
					offset: offset,
					limit: limit
				})

				offset+= EE.fieldManager.chunk_size;
			}
		})

		EE.fieldManager.update_progress(0);

		// Send ajax requests
		// Note- testing didn't show async made much improvement on time
		EE.fieldManager.sync_timeout_id = setTimeout(function() {
			EE.fieldManager.sync();
		}, 15);
	});
};

/**
 * Fire off the Ajax request, which then listens for the finish and then fires off the next Ajax request and so on
 *
 * @param
 */
EE.fieldManager.sync = function() {

	// We're done!
	if(EE.fieldManager.entriesTodo.length === 0) {
		return EE.fieldManager.finish_sync();
	}

	var data = EE.fieldManager.entriesTodo.pop();
	data.XID = EE.XID;

	$.ajax({
		url: EE.fieldManager.sync_endpoint,
		type: 'POST',
		dataType: 'json',
		data: data,
		beforeSend: function(xhr, settings) {
			// Increment the running count
			EE.fieldManager.sync_running += 1;
		},
		complete: function(xhr, textStatus) {
			// Decrement the running count
			EE.fieldManager.sync_running -= 1;

			// Update the progress bar
			var total_count       = EE.fieldManager.channel_entry_count,
				current_count     = EE.fieldManager.remainingCount.total,
				already_processed = total_count - current_count;

			EE.fieldManager.update_progress(Math.round(already_processed / total_count * 100));

			// Fire off another Ajax request
			EE.fieldManager.sync();
		},
		success: function(data, textStatus, xhr) {
			EE.fieldManager.remainingCount.total -= data.entries_proccessed;
			EE.fieldManager.remainingCount.channels[data.channel_id] -= data.entries_proccessed;

			if(EE.fieldManager.remainingCount.channels[data.channel_id] == 0) {
				EE.fieldManager.channelSynced(data.channel_id);
			}

			if (data.message_type != "success") {
				if (typeof(data.errors) != "undefined") {
					for (var key in data.errors) {
						EE.fieldManager.sync_errors.push("<b>" + key + "</b>: " + data.errors[key]);
					}
				} else {
					EE.fieldManager.sync_errors.push("<b>Undefined errors</b>");
				}
			}
		}
	});
};

/**
 * Show the sync complete summary
 *
 */
EE.fieldManager.finish_sync = function() {
	if (EE.fieldManager.sync_running == 0) {
		if (EE.fieldManager.sync_errors.length == 0) {
			// No errors? Success flashdata message should be set,
			// redirect back to the sync page to show success message
			$.ajax({
				url: EE.fieldManager.status_endpoint,
				type: 'POST',
				dataType: 'json',
				data: {'status': 'complete'},
				success: function(data, textStatus, xhr) {
					// Redirect to the return url
					window.location = EE.fieldManager.sync_returnurl;
				}
			});

		} else {
			// If there are errors, pass them through POST, there may be too
			// many to store in a flashdata cookie
			var input = $('<input>', { type: 'hidden', name: 'errors', value: JSON.stringify(EE.fieldManager.sync_errors) });
			$('.ee-main .form-standard form').append(input).submit();
		}
	};
};

EE.fieldManager.channelSynced = function(channel_id) {

	$.ajax({
		url: EE.fieldManager.status_endpoint,
		type: 'POST',
		dataType: 'json',
		data: {
			'status': 'channel_complete',
			'channel_id': channel_id
		}
	});
}

/**
 * Update the progress bar
 *
 * @param {Number} progress_percentage The percentage of progress, represented as an integer (e.g. 56 = 56%)
 */
EE.fieldManager.update_progress = function(progress_percentage) {

	var $progress = $('.progress-bar'),
		$progress_bar = $('.progress', $progress);

	if ($progress.is(':not(:visible)')) {
		$progress.show();
	};

	$progress_bar.css('width', progress_percentage+'%');
};
