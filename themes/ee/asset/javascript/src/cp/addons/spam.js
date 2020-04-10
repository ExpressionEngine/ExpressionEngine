/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/*jslint browser: true, onevar: true, undef: true, nomen: true, eqeqeq: true, plusplus: false, bitwise: true, regexp: false, strict: true, newcap: true, immed: true */

/*global $, jQuery, EE, window, document, console, alert */

"use strict";

(function ($) {
	$(".spam-detail").on('click', function(e) {
		var modal = "." + $(this).attr('rel');
		var heightIs = $(document).height();

		$('.overlay').fadeIn('slow').css('height',heightIs);
		$('.modal-wrap' + modal).fadeIn('slow');
		e.preventDefault();
		$('#top').animate({ scrollTop: 0 }, 100);

		modal = $(modal);
		modal.find('.date').html($(this).data('date'));
		modal.find('.ip').html($(this).data('ip'));
		modal.find('.content').html($(this).data('content'));
	});
	$(".update").on('click', function(e) {
		e.preventDefault();
		var link = this;
		var path = $(this).attr('href');

		$(link).toggleClass('work');

		$.ajax({
			url: path + "&method=download",
			success: function(data) {
				if ('success' in data) {
					$(link).html(data.success);
					$.ajax({
						url: path + "&method=prepare",
						success: function(data) {
							if ('success' in data) {
								updateVocabulary(link);
							}
						},
						dataType: 'json'
					});
				}
				if ('error' in data) {
					$('body').prepend(EE.alert.download_ajax_fail.replace('%s', data.error));
					$(link).removeClass('work');
				}
			},
			dataType: 'json'
		});
	});
})(jQuery);

function updateVocabulary(link) {
	var path = $(link).attr('href');
	$.ajax({
		url: path + "&method=updatevocab",
		success: function(data) {
			if (data.status !== 'finished') {
				$(link).html(data.message);
				updateVocabulary(link);
			} else {
				updateParameters(link);
			}
		},
		dataType: 'json'
	});
}

function updateParameters(link) {
	var path = $(link).attr('href');
	$.ajax({
		url: path + "&method=updateparams",
		success: function(data) {
			if (data.status !== 'finished') {
				$(link).html(data.message);
				updateParameters(link);
			} else {
				$(link).html(data.finished);
				$(link).toggleClass('work');
			}
		},
		dataType: 'json'
	});
}
