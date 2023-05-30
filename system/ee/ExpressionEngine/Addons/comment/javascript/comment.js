/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

var Comment_cp = {

	// INDEX PAGE

	table: null,
	data: null,
	html_data: null,

	detail_template: '<tr>' +
		'{{if $.isPlainObject(comment)}}<td{{each comment}}{{if $index != "data"}} ${$index}="${$value}" {{/if}}{{/each}}>{{html comment.data}}{{else}}<td>{{html comment}}{{/if}}</td>' +
		'{{if $.isPlainObject(details_link)}}<td{{each details_link}}{{if $index != "data"}} ${$index}="${$value}" {{/if}}{{/each}}>{{html details_link.data}}{{else}}<td>{{html details_link}}{{/if}}</td>' +
	'</tr>',

	setup_index: function() {

		this.table = $('.mainTable');

		$.template('comment_additional_row', this.detail_template);

		this.toggles();
		this.table_data();
		this.table_events();
		this.ajax_filter();
	},

	toggles: function() {
		$("#target").submit(function () {
			if ( ! $("input[class=comment_toggle]", this).is(":checked")) {
				$.ee_notice(EE.lang.selection_required, {"type" : "error"});
				return false;
			}
		});
	},

	table_data: function() {
		this.data = this.table.table('get_current_data').rows,
		this.html_rows = this.table.find('tbody tr');
	},

	table_events: function() {
		var that = this,
			indicator = $('.searchIndicator');

		this.table.bind('tableupdate', function(evt, res) {
			that.html_rows = $(res.data.html_rows);
			that.data = res.data.rows;
		}).bind('tableload', function() {
			indicator.css('visibility', '');
		})
		.bind('tableupdate', function() {
			indicator.css('visibility', 'hidden');
		});

		this.table.delegate('.expand', 'click', function() {
			var el = $(this),
				current_row = el.closest('tr');

			if (el.data('expanded')) {
				that._collapse(el, current_row);
			} else {
				that._expand(el, current_row);
			}

			return false;
		});
	},

	ajax_filter: function() {
		this.table.table('add_filter', $('#comment_filter'));
	},

	_collapse: function(el, current_row) {
		// remove row
		current_row.next('tr').remove();

		// flag and image
		el.data('expanded', false);
		el.find('img').attr('src', EE.THEME_URL + "images/field_collapse.png");
	},

	_expand: function(el, current_row) {
		// parse row
		var index = Comment_cp.html_rows.index(current_row),
			new_row = $.tmpl('comment_additional_row', Comment_cp.data[index]);

		// add it
		current_row.after(new_row);

		// flag and image
		el.data('expanded', true);
		el.find('img').attr('src', EE.THEME_URL + "images/field_expand.png");
	},


	// EDIT PAGE

	setup_edit: function() {
		var $move_link = $('#move_link'),
			$move_field = $('#move_field');

		// If validation fails- want to be sure to show the move field if populated
		if ($("#move_to").val() != "") {
			$move_link.hide();
			$move_field.show();
		}

		$("#move_link").click(function() {
			$move_link.hide();
			$move_field.show();
			return false;
		});

		$("#cancel_link").click(function() {
			$("input#move_to").val("");
			$move_link.show();
			$move_field.hide();
			return false;
		});
	}

};


// run_script is set in the controller
if (EE.comment && EE.comment.run_script) {
	setTimeout(function() {
		var script = EE.comment.run_script;
		Comment_cp[script]();
	}, 100);
}
