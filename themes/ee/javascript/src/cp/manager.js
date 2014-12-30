/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */
/* This file exposes three callback functions:
 *
 * EE.manager.showPrefsRow and EE.manager.hidePrefsRow and
 * EE.manager.refreshPrefs
 */

/*jslint browser: true, onevar: true, undef: true, nomen: true, eqeqeq: true, plusplus: false, bitwise: true, regexp: false, strict: true, newcap: true, immed: true */

/*global $, jQuery, EE, window, document, console, alert */

"use strict";

function refresh_prefs_ajax(id) {

	$.ajax({
		type: "GET",
		url: EE.template_prefs_url,
		data: "is_ajax=TRUE&group_id=" + id,
		dataType: 'json',
		success: function (data) {
			EE.pref_json = data;
		}
	});
}

function access_edit_ajax(element) {

	var template_id,
		ids,
		no_auth_bounce,
		payload = [];

	// We may be changing permissions for multiple element at a time
	// if they selected a Select All option
	element.each(function(index, el)
	{
		var el = $(el);

		// Handle template bounce setting
		if (el.attr('name').substr(0, 14) === 'no_auth_bounce')
		{
			template_id = (el.attr('name').substr(15))
				? el.attr('name').substr(15) : $('input:hidden[name=template_id]').val();

			payload.push({
				template_id: template_id,
				no_auth_bounce: el.val()
			});
		}
		// Handle enabling HTTP authentication for a template
		else if (el.attr('name').substr(0, 16) === 'enable_http_auth')
		{
			template_id = (el.attr('name').substr(17))
				? el.attr('name').substr(17) : $('input:hidden[name=template_id]').val();

			payload.push({
				template_id: template_id,
				enable_http_auth: el.val()
			});
		}
		// Handle template route
		else if (el.attr('name').substr(0, 14) === 'template_route')
		{
			template_id = (el.attr('name').substr(15))
				? el.attr('name').substr(15) : $('input:hidden[name=template_id]').val();

			payload.push({
				template_id: template_id,
				template_route: el.val()
			});
		}
		// Handle template route required
		else if (el.attr('name').substr(0, 14) === 'route_required')
		{
			template_id = (el.attr('name').substr(15))
				? el.attr('name').substr(15) : $('input:hidden[name=template_id]').val();

			payload.push({
				template_id: template_id,
				route_required: el.val()
			});
		}
		// Handle member group permissions for this template
		else
		{
			ids = el.attr('name').replace('access_', '').split('_');
			template_id = (ids.length < 2) ? $('input:hidden[name=template_id]').val() : ids[1];
			no_auth_bounce = (! $(el).closest('.accessTable').length)
				? $('.no_auth_bounce').val() :  $(el).closest('.accessTable').find('.no_auth_bounce').val();

			el.attr('checked', 'checked');

			payload.push({
				template_id: template_id,
				member_group_id: ids[0],
				new_status: el.val(),
				no_auth_bounce: no_auth_bounce
			});
		}
	});

	$.ajax({
		type: "POST",
		url: EE.access_edit_url,
		data: {is_ajax: 'TRUE', XID: EE.XID, payload: payload},
		success: function (msg) {
			if (msg !== '') {
				$.ee_notice(msg, {duration: 3000, type: 'success'});
			}
		},
		error: function (req, error) {
			if (req.responseText !== '') {
				$.ee_notice(req.responseText, {duration: 3000, type: 'error'});
			}
		}
	});
}


function template_edit_ajax() {

	var holder = $(this).closest('.accessRowHeader'),
		holder_data,
		template_id, group_id, template_name, template_type,
		cache, refresh, allow_php, php_parse_location, hits,
		template_size, protect_javascript, str;

	if (holder.length < 1) {
		holder = $(this).closest('.templateEditorTable');
	}

	holder_data = holder.data('ajax_ids');

	if (! holder_data) {
		if ($(this).hasClass("ignore_radio")) {
			return false;
		}
		return access_edit_ajax($(this));
	}

	template_id = holder_data.id;
	group_id = holder_data.group_id;

	template_name = holder.find(".template_name").val();
	template_type = holder.find("select[name^=template_type]").val();
	cache = holder.find("select[name^=cache]").val();
	refresh = holder.find(".refresh").val();
	allow_php = holder.find("select[name^=allow_php]").val();
	php_parse_location = holder.find("select[name^=php_parse_location]").val();
	hits = holder.find(".hits").val();
	template_size = holder.find(".template_size").val();
	protect_javascript = holder.find("select[name^=protect_javascript]").val();

	str = jQuery.param({
		'template_id': template_id,
		'group_id': group_id,
		'template_name': template_name,
		'template_type': template_type,
		'cache': cache,
		'refresh': refresh,
		'hits': hits,
		'allow_php': allow_php,
		'php_parse_location': php_parse_location,
		'template_size': template_size,
		'protect_javascript': protect_javascript
	});

	$.ajax({
		type: "POST",
		url: EE.template_edit_url,
		data: "is_ajax=TRUE&XID=" + EE.XID + "&" + str,
		success: function (msg) {

			var name_obj = $("#templateId_" + template_id),
				view_link;


			// change the displayed template name
			name_obj.text(template_name);

			// Change the view link
			if (name_obj.closest('.templateName').length) {
				view_link = name_obj.closest('.templateName').next().find('a');
				if (view_link.length) {
					view_link = view_link.get(0);
					view_link.href = view_link.href.replace(/\/[^\/]*$/, '/' + template_name);
				}
			}
			else if ($('#templateViewLink a.submit').length) {
				view_link = $('#templateViewLink a.submit');

				if (view_link.length) {
					view_link = view_link.get(0);
					view_link.href = view_link.href.replace(/\/[^\/]*$/, '/' + template_name);

				}
			}

			// change the displayed template size
			$("#template_data").attr('rows', template_size);

			// change the displayed hits
			$("#hitsId_" + template_id).text(hits);

			if (msg !== '') {
				$.ee_notice(msg, {duration: 3000, type: 'success'});
			}
		},
		error: function (req, error) {
			if (req.responseText !== '') {
				$.ee_notice(req.responseText, {duration: 3000, type: 'error'});
			}
		}
	});
}

function hideSubRows(currentrow, type) {

	if (type) {
		if ($(currentrow).data(type)) {
			$(currentrow).data(type).hide();
		}
		return;
	}

	hideSubRows(currentrow, 'prefsRow');
	hideSubRows(currentrow, 'accessRow');
}


function hideRow(currentrow, data_type) {
	if (currentrow.hasClass('highlightRow')) {
		currentrow.removeClass('highlightRow');
	}

	if (currentrow.data(data_type)) {
		var was_vis = currentrow.data(data_type).is(':visible');
		hideSubRows(currentrow);

		if (! was_vis) {
			currentrow.addClass('highlightRow');
			currentrow.data(data_type).show();
		}
		return true;
	}

	hideSubRows(currentrow);
	return false;
}

function set_radio_buttons(parent, data) {

	parent.find('input:radio').each(function () {
		var parts, name, option;

		parts = $(this).attr('id').split('_');

		name = parts.slice(0, -1).join('_');
		option = parts.slice(-1)[0];

		$(this).attr({
			'id': name + '_' + data + '_' + option,
			'name': name + '_' + data
		});
	});
}

function bind_prefs_events() {
	$('.templateTable .accessTable').find('input:text').unbind('blur.manager_updated').bind('blur.manager_updated', template_edit_ajax);
	$('.templateTable .accessTable').find('input:radio').unbind('click.manager_updated').bind('click.manager_updated', template_edit_ajax);
	$('.templateTable .accessTable').find('select').unbind('change.manager_updated').bind('change.manager_updated', template_edit_ajax);
}

(function ($) {

	var prefs_template, access_template;

	$(document).ready(function () {

		var tables, template_id, group_id;

		prefs_template = $('#prefRowTemplate').html();
		access_template = $('#accessRowTemplate').html();

		function all_checkbox_toggles(parent, template_id) {
			var selector_base = 'input:radio[id$=_';

			if (template_id) {
				selector_base = 'input:radio[id$=_' + template_id + '_';
			}

			parent.find('.ignore_radio').click(function () {
				if (this.value === 'y' || this.value === 'n')
				{
					access_edit_ajax(
						parent.find(selector_base + this.value + ']').filter(':not(.ignore_radio)')
					);
				}

				$(this).attr('checked', false);
				return false;
			});
		}


		function createAccessRow(template_id, currentrow, rowdata) {
			var headerrow = $('<tr class="accessRowHeader"><td colspan="6">' + access_template + '</td></tr>');

			// no_auth_bounce field
			headerrow.find('.no_auth_bounce').val(rowdata.no_auth_bounce);
			headerrow.find('.no_auth_bounce').attr({
				'id': 'no_auth_bounce_' + template_id,
				'name': 'no_auth_bounce_' + template_id
			});

			// http auth
			headerrow.find(".enable_http_auth").val(rowdata.enable_http_auth);
			headerrow.find('.enable_http_auth').attr({
				'id': 'enable_http_auth_' + template_id,
				'name': 'enable_http_auth_' + template_id
			});

			// template route
			headerrow.find(".template_route").val(rowdata.template_route);
			headerrow.find('.template_route').attr({
				'id': 'template_route_' + template_id,
				'name': 'template_route_' + template_id
			});

			// template route required
			headerrow.find(".route_required").val(rowdata.route_required);
			headerrow.find('.route_required').attr({
				'id': 'route_required_' + template_id,
				'name': 'route_required_' + template_id
			});

			// Set data, ids, and names

			// Radio Buttons
			set_radio_buttons(headerrow, template_id);

			$.each(rowdata.access, function (id, data) {
				var radio_y = headerrow.find('#access_' + id + '_' + template_id + '_y'),
					radio_n = headerrow.find('#access_' + id + '_' + template_id + '_n');

				if (data.access === true) {
					radio_y.attr('checked', 'checked');
					radio_n.attr('checked', false);
				} else {
					radio_n.attr('checked', 'checked');
					radio_y.attr('checked', false);
				}
			});

			all_checkbox_toggles(headerrow, template_id);

			$(currentrow).addClass('highlightRow');
			$(currentrow).after(headerrow);

			// Restripe!
			headerrow.find('.accessTable').tablesorter({
				widgets: ["zebra"]
			});

			currentrow.data('accessRow', headerrow);
		}


		function createPrefsRow(currentrow, rowdata) {
			var headerrow = $('<tr class="accessRowHeader"><td colspan="6">' + prefs_template + '</td></tr>');

			// Set data, ids, and names

			// Dropdowns
			headerrow.find('select').each(function () {
				var field = $(this);

				switch (this.name) {
				case 'template_type':
					field.val(rowdata.type);
					break;
				case 'cache':
					field.val(rowdata.cache);
					break;
				case 'allow_php':
					field.val(rowdata.allow_php);
					break;
				case 'php_parse_location':
					field.val(rowdata.php_parsing);
					break;
				case 'protect_javascript':
					field.val(rowdata.protect_javascript);
					break;
				}

				field.attr("name", this.name + '_' + rowdata.id);
			});

			// Name field
			headerrow.find('.template_name').val(rowdata.name);
			if (rowdata.name === 'index') {
				headerrow.find('.template_name').attr({readonly: 'readonly'});
			}

			// Refresh Interval
			headerrow.find('.refresh').val(rowdata.refresh);

			// Hit Count
			headerrow.find('.hits').val(rowdata.hits);

			// Entry id and group id
			headerrow.data('ajax_ids', {'id': rowdata.id, 'group_id': rowdata.group_id});
			currentrow.data('prefsRow', headerrow);

			$(currentrow).addClass('highlightRow');
			$(currentrow).after(headerrow);
		}

		// Expose the three click callback functions - events bound in the controller
		EE.manager = {
			refreshPrefs: function (id) {

				refresh_prefs_ajax(id);
			},
			showPrefsRow: function (rowdata, el) {

				var currentrow = $(el).parent().parent();

				if (! hideRow(currentrow, 'prefsRow'))
				{
					createPrefsRow(currentrow, rowdata);
					bind_prefs_events();
				}

				return false;
			},

			showAccessRow: function (template_id, rowdata, el) {

				var currentrow = $(el).parent().parent();

				if (! hideRow(currentrow, 'accessRow'))
				{
					createAccessRow(template_id, currentrow, rowdata);
					bind_prefs_events();
					currentrow.trigger("applyWidgets");
				}
				return false;
			}
		};

		// Template editor page?
		if (! prefs_template || ! access_template)
		{
			tables = $('#templateAccess, #templatePreferences');
			template_id = $('input:hidden[name=template_id]').val();
			group_id = $('input:hidden[name=group_id]').val();

			$('#templatePreferences').data('ajax_ids', {'id': template_id, 'group_id': group_id});

			all_checkbox_toggles($('#templateAccess'));

			tables.find('input:text').unbind('blur.manager_updated').bind('blur.manager_updated', template_edit_ajax);
			tables.find('input:radio').unbind('click.manager_updated').bind('click.manager_updated', template_edit_ajax);
			tables.find('select').unbind('change.manager_updated').bind('change.manager_updated', template_edit_ajax);

			return;
		}

		$('#prefRowTemplate, #accessRowTemplate').remove();
	});

	$('.last_edit').css('opacity', 0).show();

	$('#template_details').hover(function() {
		$('.last_edit').animate({'opacity': 1}, 50);
	}, function() {
		$('.last_edit').animate({'opacity': 0}, 50);
	});


	// Template search reset
	$('#template_keywords_reset').click(function(){
		$('#template_keywords').val('');
		$('.search form').submit();
	});

})(jQuery);