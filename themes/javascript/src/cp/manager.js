/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
/* This file exposes two callback functions:
 * 
 * EE.manager.showPrefsRow and EE.manager.hidePrefsRow
 */

/*jslint browser: true, onevar: true, undef: true, nomen: true, eqeqeq: true, plusplus: false, bitwise: true, regexp: false, strict: true, newcap: true, immed: true */

/*global $, jQuery, EE, window, document, console, alert */

"use strict";

function _access_edit_ajax(el, template_id, m_group_id, kind) {
	
	var str = '',
		no_auth_bounce;
	
	switch (kind) {
	case 'no_auth_bounce':
		str = jQuery.param({
			'template_id': template_id,
			'no_auth_bounce': el.val()
		});
		break;
	case 'enable_http_auth':
		str = jQuery.param({
			'template_id': template_id,
			'enable_http_auth': el.val()
		});
		break;
	case 'access':
		no_auth_bounce = (! $(el).closest('.accessTable').length) ?
								 $('.no_auth_bounce').val() :
								 $(el).closest('.accessTable').find('.no_auth_bounce').val();
		str = jQuery.param({
			'template_id': template_id,
			'member_group_id': m_group_id,
			'new_status': el.val(),
			'no_auth_bounce' : no_auth_bounce
		});
		break;
	}
	

	$.ajax({
		type: "POST",
		url: EE.access_edit_url,
		data: "is_ajax=TRUE&XID=" + EE.XID + "&" + str,
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


function access_edit_ajax(el) {
	
	var ids, template_id;
	
	// access_gid_tid
	if (el.attr('name').substr(0, 14) === 'no_auth_bounce') {
		template_id = (el.attr('name').substr(15)) ? el.attr('name').substr(15) : $('input:hidden[name=template_id]').val();
		_access_edit_ajax(el, template_id, '', 'no_auth_bounce');
	}
	else if (el.attr('name').substr(0, 16) === 'enable_http_auth') {
		template_id = (el.attr('name').substr(17)) ? el.attr('name').substr(17) : $('input:hidden[name=template_id]').val();
		_access_edit_ajax(el, template_id, '', 'enable_http_auth');
	} else {
		ids = el.attr('name').replace('access_', '').split('_');
		template_id = (ids.length < 2) ? $('input:hidden[name=template_id]').val() : ids[1];

		_access_edit_ajax(el, template_id, ids[0], 'access');
	}
}



function template_edit_ajax() {
	
	var holder = $(this).closest('.accessRowHeader'),
		holder_data,
		template_id, group_id, template_name, template_type,
		cache, refresh, allow_php, php_parse_location, hits,
		template_size, str;

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
	template_type = holder.find(".template_type").val();
	cache = holder.find("select[name^=cache]").val();
	refresh = holder.find(".refresh").val();
	allow_php = holder.find("select[name^=allow_php]").val();
	php_parse_location = holder.find("select[name^=php_parse_location]").val();
	hits = holder.find(".hits").val();
	template_size = holder.find(".template_size").val();

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
		'template_size': template_size
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
				if (this.value === 'y') {
					parent.find(selector_base + 'y]').filter(':not(.ignore_radio)').trigger('click');
				}
				if (this.value === 'n') {
					parent.find(selector_base + 'n]').filter(':not(.ignore_radio)').trigger('click');
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
		
		// Expose the two click callback functions - events bound in the controller
		EE.manager = {
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
	});

	$('.last_edit').css('opacity', 0).show();
	
	$('#template_details').hover(function() {
		$('.last_edit').animate({'opacity': 1}, 50);
	}, function() {
		$('.last_edit').animate({'opacity': 0}, 50);
	});
		

	// Find and replace template stuff
	$(document).ready(function () {
		
		if (! EE.manager || ! EE.manager.warnings) {
			return;
		}
		
		$('.warning_details').hide();
		$('.toggle_warning_details').click(function () {
			$('.warning_details').hide();
			$('#wd_' + this.id.substr(3)).show();
			return false;
		});

		var txtarea = $('#template_data'), 
			selection, find_and_replace;

		find_and_replace = function (find, replace, dropdown) {
			var text, select = '';
			
			if (dropdown && dropdown.length > 1) {
				select = '<select name="fr_options" id="fr_options"></select>';
			}
			
			text = '<div style="padding: 5px;"><label>Find:</label> <input name="fr_find" id="fr_find" type="text" value="" /> <label>Replace:</label> <input type="text" name="fr_replace" id="fr_replace" value=""/> ' + select + '</div>';
			text +=	'<div style="padding: 5px;"><button class="submit" id="fr_find_btn">Find Next</button> <button class="submit" id="fr_replace_btn">Replace</button> <button class="submit" id="fr_replace_all_btn">Replace All</button> <label><input name="fr_replace_closing_tags" id="fr_replace_closing_tags" type="checkbox" /> Include Closing Tags</label></div>';

			$.ee_notice(text, {
				type: "custom",
				open: true,
				close_on_click: false
			});

			$('#fr_find').val(find);
			$('#fr_replace').val(replace);
			$('#fr_replace_closing_tags').attr('checked', false);
			
			if (select !== '') {
				$('#fr_options').append($(dropdown));
				$('#fr_options').click(function () {
					$('#fr_find').val($(this).val());
					$('#fr_find_btn').click();
				});
			}

			if (find) {
				$('#fr_find_btn').click();
			}
		};

		$('#fr_find_btn').live('click', function () {
			var find = $('#fr_find').val();		
			selection = txtarea.selectNext(find).scrollToCursor();
		});

		$('#fr_replace_btn').live('click', function () {
			var find = $('#fr_find').val(),
				replace = $('#fr_replace').val();

			if (selection.getSelectedText() === find) {
				selection.replaceWith(replace);
			}
		});

		$('#fr_replace_all_btn').live('click', function () {
			var find = $('#fr_find').val(),
				replace = $('#fr_replace').val();

			// Sanity check
			if (jQuery.trim(find) === '') {
				return;
			}
			
			// str.replace can only do one item at a time - or a regex ... might consider
			// the latter as an option in the future, but for now we'll split and rejoin.
			
			txtarea.val(txtarea.val().split(find).join(replace));
			
			if ($('#fr_replace_closing_tags').attr('checked')) {

				if (find[0] === '{' && find.substr(0, 2) !== '{/') {
					find = '{/' + find.substr(1);
				}
				if (replace[0] === '{' && replace.substr(0, 2) !== '{/') {
					replace = '{/' + replace.substr(1);
				}
				
				if (jQuery.trim(find) === '') {
					return;
				}
				
				txtarea.val(txtarea.val().split(find).join(replace));
			}
		});

		$('.find_and_replace').click(function () {
			var tag_name = this.id.substr(8),
				find = '{exp:' + tag_name,
				suggest = '{exp:' + EE.manager.warnings[tag_name].suggestion,
				full_tags = EE.manager.warnings[tag_name].full_tags,
				tags = new Array(new Option(find, find)),
				i;
			
			if (full_tags && full_tags.length > 1) {
				for (i = 0; i < full_tags.length; i++) {
					tag_name = '{' + full_tags[i] + '}';
					tags.push(new Option(tag_name, tag_name));
				}
			}
			
			if (suggest === '{exp:') {
				suggest = '';
			}

			find_and_replace(find, suggest, tags);
			return false;
		});
	});
	
	
	// Template search reset
	$('#template_keywords_reset').click(function(){
		$('#template_keywords').val('');
		$('.search form').submit();
	});


})(jQuery); 