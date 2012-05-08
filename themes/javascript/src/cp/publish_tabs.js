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

var selected_tab = "";

function get_selected_tab() {
	return selected_tab;
}

function tab_focus(tab_id)
{
	// If the tab was hidden, this was triggered
	// through the sidebar - show it again!
	if ( ! $(".menu_"+tab_id).parent().is(":visible")) {
		// we need to trigger a click to maintain
		// the delete button toggle state
		$("a.delete_tab[href=#"+tab_id+"]").trigger("click");
	}

	$(".tab_menu li").removeClass("current");
	$(".menu_"+tab_id).parent().addClass("current");
	$(".main_tab").hide();
	$("#"+tab_id).show();
	$(".main_tab").css("z-index", "");
	$("#"+tab_id).css("z-index", "5");
	selected_tab = tab_id;
	
	$(".main_tab").sortable("refreshPositions");
}

// @todo hacky, hacky, hacky
EE.tab_focus = tab_focus;

function setup_tabs() {
	var spring_delay = 500,
		focused_tab = "menu_publish_tab",
		field_dropped = false,
		spring = "";

	$(".tab_menu li a").droppable({
		accept: ".field_selector, .publish_field",
		tolerance: "pointer",
		forceHelperSize: true,
		deactivate: function(e, ui) {
			clearTimeout(spring);
			$(".tab_menu li").removeClass("highlight_tab");
		},
		drop: function(e, ui) {
		    field_id = ui.draggable.attr("id").substring(11);
		    tab_id = $(this).attr("title").substring(5);

		    setTimeout(function() {
		        $("#hold_field_"+field_id).prependTo("#"+tab_id);
		        $("#hold_field_"+field_id).hide().slideDown();
		    }, 0);

		    // bring focus
		    tab_focus(tab_id);
		    return false;
		},
		over: function(e, ui) {

			tab_id = $(this).attr("title").substring(5);
			$(this).parent().addClass("highlight_tab");
				spring = setTimeout(function() {
				tab_focus(tab_id);
				return false;
			}, spring_delay);
		},
		out: function(e, ui) {
			if (spring != "") {
				clearTimeout(spring);
			}
			$(this).parent().removeClass("highlight_tab");
		}
	});

	$("#holder .main_tab").droppable({
		accept: ".field_selector",
		tolerance: "pointer",
		drop: function(e, ui) {
			field_id = (ui.draggable.attr("id") == "hide_title" || ui.draggable.attr("id") == "hide_url_title") ? ui.draggable.attr("id").substring(5) : ui.draggable.attr("id").substring(11);
						tab_id = $(this).attr("id");

			// store the field we are moving, then remove it from the DOM
			$("#hold_field_"+field_id).prependTo("#"+tab_id);

			$("#hold_field_"+field_id).hide().slideDown();
		}
	});

	$(".tab_menu li.content_tab a, #publish_tab_list a.menu_focus")
		.unbind(".publish_tabs")
		.bind("mousedown.publish_tabs", function(e) {
			tab_id = $(this).attr("title").substring(5);
			tab_focus(tab_id);
			e.preventDefault();
		}).bind("click.publish_tabs", function() {
			return false;
	});

	setTimeout(function() {
		// allow sorting of publish fields
		$(".main_tab").sortable({
			connectWith: '.main_tab',
			appendTo: '#holder',
			helper: 'clone',
			forceHelperSize: true,
			handle: ".handle",
			start: function(event, ui) {
				ui.item.css("width", $(this).parent().css("width"));
			},
			stop: function(event, ui) {
				ui.item.css("width", "100%");
			}
		});
	}, 1500); // I know this seems excessive, but for some reason this setup code takes almost half a second, so we want it to run dead last!
}

$(".tab_menu li:first").addClass("current");
setup_tabs();