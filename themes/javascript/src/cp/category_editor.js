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

"use strict";

EE.publish = EE.publish || {};

// The functions in this file are called from within publish if their components
// are needed. So for example EE.publish.category_editor() is called after
// the category menu is constructed.

EE.publish.category_editor = function() {
	var cat_groups = [],
		cat_modal = $('<div />'),
		cat_modal_container = $('<div id="cat_modal_container" />').appendTo(cat_modal),
		cat_groups_containers = {},
		cat_groups_buttons = {},
		cat_list_url = EE.BASE+'&C=admin_content&M=category_editor&group_id=',
		refresh_cats, setup_page, reload, i,
		selected_cats = {},
		$editor_container = $('<div />');

	// categories with a lot of custom fields need to scroll
	cat_modal_container.css({
		height: '100%',
		padding: '0 20px 0 0',	// account for vert scrollbar
		overflow: 'auto'
	});

	// IE caches $.load requests, so we need a unique number
	function now() {
		return +new Date();
	}

	cat_modal.dialog({
		autoOpen: false,
		height: 475,
		width: 600,
		modal: true,
		resizable: false,
		title: EE.publish.lang.edit_category,
		open: function(event, ui) {
			$('.ui-dialog-content').css('overflow', 'hidden');
			$('.ui-dialog-titlebar').focus(); // doing this first to fix IE7 scrolling past the dialog's close button
			$('#cat_name').focus();

			// Create listener for file field
			EE.publish.file_browser.category_edit_modal();
		}
	});

	// Grab all group ids
	$(".edit_categories_link").each(function() {
		var gid = this.href.substr(this.href.indexOf("=") + 1);
		var amp = gid.indexOf("&");

		if (amp != -1) {
			gid = gid.substr(0, amp);
		}

		$(this).data("gid", gid);
		cat_groups.push(gid);
	});

	for (i = 0; i < cat_groups.length; i++) {
		cat_groups_containers[cat_groups[i]] = $("#cat_group_container_"+[cat_groups[i]]);
		cat_groups_containers[cat_groups[i]].data("gid", cat_groups[i]);
		cat_groups_buttons[cat_groups[i]] = $("#cat_group_container_"+[cat_groups[i]]).find(".cat_action_buttons").remove();
	}

	refresh_cats = function(gid) {
		cat_groups_containers[gid].text("loading...").load(cat_list_url+gid+"&timestamp="+now()+" .pageContents table", function() {
			setup_page.call(cat_groups_containers[gid], cat_groups_containers[gid].html(), false);
		});
	};

	// A function to setup new page events
	setup_page = function(response, require_valid_response) {
		var container = $(this),
			gid = container.data("gid");

		response = $.trim(response);

		if (container.hasClass('edit_categories_link')) {
			container = $("#cat_group_container_"+gid);
		}

		if (response.charAt(0) !== '<' && require_valid_response) {
			return refresh_cats(gid);
		}

		container.closest(".cat_group_container").find("#refresh_categories").show();

		var res = $(response),
			form = res.find("form"),
			submit_button, container_form,
			$category_name, $category_url_title;

		if (form.length) {
			cat_modal_container.html(res);

			submit_button = cat_modal_container.find("input[type=submit]");
			container_form = cat_modal_container.find("form");
			$category_name = container_form.find('#cat_name');
			$category_url_title = container_form.find('#cat_url_title');

			$category_name.keyup(function(event) {
				$category_name.ee_url_title($category_url_title);
			});

			var handle_submit = function(form) {
				var that = form || $(this),
					values = that.serialize(),
					url = that.attr("action");

				$.ajax({
					url: url,
					type: "POST",
					data: values,
					dataType: "html",
					beforeSend: function() {
						$editor_container.html(EE.lang.loading);
					},
					success: function(res) {
						res = $.trim(res);
						cat_modal.dialog("close");

						if (res[0] == '<') {
							var response = $(res).find(".pageContents"),
								form = response.find("form");

							if (form.length == 0) {
								$editor_container.html(response);
							}

							response = response.wrap('<div />').parent(); // outer html hack
							setup_page.call(container, response.html(), true);
						}
						else {
							setup_page.call(container, res, true);
						}
					},
					error: function(res) {
						res = $.parseJSON(res.responseText);
						// cat_modal.dialog("close");
						cat_modal.html(res.error);
						// setup_page.call(container, res.error, true);
					}
				});

				return false;
			};

			container_form.submit(handle_submit);

			var buttons = {};
			buttons[submit_button.remove().attr('value')] = {
				text: EE.publish.lang.update,
				click: function() {
					handle_submit(container_form);
				}
			};

			cat_modal.dialog("open");
			cat_modal.dialog("option", "buttons", buttons);

			cat_modal.one('dialogclose', function() {
				refresh_cats(gid);
			});
		}
		else {
			cat_groups_buttons[gid].clone().appendTo(container).show();
		}

		return false;
	};

	// And a function to do the work
	reload = function(event) {
		event.preventDefault();

		var link = $(this).hide(),
			gid = $(this).data("gid"),
			resp_filter = ".pageContents";

		if ($(this).hasClass("edit_cat_order_trigger") || $(this).hasClass("edit_categories_link")) {
			resp_filter += " table";
		}

		if ( ! gid) {
			gid = $(this).closest(".cat_group_container").data("gid");
		}

		// Grab selection if checkboxes are available
		if ($(this).hasClass("edit_categories_link"))
		{
			selected_cats[gid] = cat_groups_containers[gid].find('input:checked').map(function() {
				return this.value;
			}).toArray();
		}

		// Hide the checkboxes instead of destroying them in case publish form is
		// submitted while the category editor is still showing
		cat_groups_containers[gid].find('label').hide();

		cat_groups_containers[gid].append($editor_container.html(EE.lang.loading));

		$.ajax({
			url: $(this).attr('href') + "&timestamp="+now() + resp_filter,
			dataType: "html",
			success: function(response) {
				var res,
					filtered_res = '';

				response = $.trim(response);

				if (response.charAt(0) == '<') {
					res = $(response).find(resp_filter);

					filtered_res = $('<div />').append(res).html();
					if (res.find('form').length == 0) {
						$editor_container.html(filtered_res);
					}
				}

				setup_page.call(cat_groups_containers[gid], filtered_res, true);
			},
			error: function(response) {
				response = $.parseJSON(response.responseText);
				$editor_container.text(response.error);
				setup_page.call(cat_groups_containers[gid], response.error, true);
			}
		});
	};

	// Hijack edit category links to get it off the ground
	$(".edit_categories_link").click(reload);

	// Hijack internal links (except for done and adding filename)
	$('.cat_group_container a:not(.cats_done, .choose_file)').live('click', reload);

	// Last but not least - update the checkboxes
	$(".cats_done").live("click", function() {
		var that = $(this).closest(".cat_group_container"),
			gid = that.data("gid");

		$(".edit_categories_link").each(function(el, i) {
			if ($(this).data("gid") == gid) {
				$(this).show();
			}
		});

		that.text("loading...").load(EE.BASE+"&C=content_publish&M=category_actions&group_id="+that.data("gid")+"&timestamp="+now(), function(response) {
			that.html( $(response).html() );

			$.each(selected_cats[gid], function(k, v) {
				that.find('input[value='+v+']').attr('checked', 'checked');
			});
		});

		return false;
	});
};

$(document).ready(function() {
	EE.publish.category_editor();
});