// Create a void console.log if the
// browser does not support it

if (typeof console == "undefined" || ! console.log) {
	console = { log: function() { return false; }};
}


// Setup Base EE Control Panel

jQuery(document).ready(function() {

var $ = jQuery;


// Setup Global Ajax Events

// A 401 in combination with a url indicates a redirect, we use this
// on the login page to catch periodic ajax requests (e.g. autosave)

$(document).bind('ajaxComplete', function(evt, xhr) {
	if (xhr.hasOwnProperty('status') && xhr.status == 401) {
		document.location = EE.BASE+'&'+xhr.responseText;
	}
});


// OS X Style Search Boxes for Webkit

EE.create_searchbox = (function() {
	
	function webkit_search(el, placeholder, save) {
		el.setAttribute('type', 'search');
		$(el).attr({
			autosave: 		save,
			results:		'10',
			placeholder:	placeholder
		});
	}
	
	function generic_search(el, placeholder) {
		var jqEl = $(el),
			orig_color = jqEl.css('color');
		
		jqEl.focus(function() {
			// Reset color & remove placeholder text
			jqEl.css('color', orig_color);
			(jqEl.val() == placeholder && jqEl.val(''));
		})
		.blur(function() {
			// If no user content -> add placeholder text and dim
			if (jqEl.val() == '' || jqEl.val == placeholder) {
				jqEl.val(placeholder).css('color', '#888');
			}
		})
		.trigger('blur');
	}
	
	var create_func = (parseInt(navigator.productSub) >= 20020000 &&
					  navigator.vendor.indexOf('Apple Computer') != -1) ? webkit_search : generic_search;

	return function(id, placeholder, save) {
		var el = document.getElementById(id);
		(el && create_func(el, placeholder, save));
	}
})();

// @todo Language keys
EE.create_searchbox('cp_search_keywords', 'Search', 'ee_cp_search');
EE.create_searchbox('template_keywords', 'Search Templates', 'ee_template_search');


// External links open in new window

$('a[rel="external"]').click(function() {
	window.open(this.href);
	return false;
});


// Hook up show / hide actions for sidebar

function show_hide_sidebar() {
	var w = {'revealSidebarLink': '77%', 'hideSidebarLink': '100%'},
		main_content = $("#mainContent");
	
	// Sidebar state

	if (EE.CP_SIDEBAR_STATE == "off") {
		main_content.css("width", "100%");
		$("#revealSidebarLink").css('display', 'block');
		$("#hideSidebarLink").hide();
	}
	
	$('#revealSidebarLink, #hideSidebarLink').click(function() {
		var that = $(this),
			other = that.siblings('a');
		
		that.hide().siblings(':not(#activeUser)').slideToggle();
		main_content.animate({"width": w[this.id]});
		other.show();
		return false;
	});
}

show_hide_sidebar();


// Move notices to notification bar for consistency

if (EE.flashdata !== undefined) {
	var notices = $(".notice");
		types = {success: "message_success", notice: "message", error: "message_failure"},
		show_notices = [];

	for (type in types) {
		if (EE.flashdata.hasOwnProperty(types[type])) {

			if (type == "error") {
				notice = notices.filter(".failure").slice(0, 1);
			}
			else if (type == "success") {
				notice = notices.filter(".success").slice(0, 1);
			}
			else {
				notice = notices.slice(0, 1);
			}

			if (EE.flashdata[types[type]] == notice.html()) {
				show_notices.push({message: EE.flashdata[types[type]], type: type});
				notice.remove();
			}
		}
	}

	if (show_notices.length) {
		$.ee_notice(show_notices);
	}
}


// Setup Notepad

EE.notepad = (function() {
	var notepad = $('#notePad'),
		notepad_form = $("#notepad_form"),
		notepad_desc = $('#sidebar_notepad_edit_desc'),
		notepad_txtarea = $('#notePadTextEdit'),
		notepad_controls = $('#notePadControls'),
		notepad_text = $('#notePadText').removeClass('js_show'),	// .show() was really slow on this - not sure why
		notepad_empty = notepad_text.text(),
		current_content = notepad_txtarea.val();
	
	return {
		init: function() {
			if (current_content) {
				notepad_text.html(current_content.replace(/</ig, '&lt;').replace(/>/ig, '&gt;').replace(/\n/ig, '<br />'));
			}
			
			notepad.click(EE.notepad.show);
			notepad_controls.find('a.cancel').click(EE.notepad.hide);
			
			notepad_form.submit(EE.notepad.submit);
			notepad_controls.find('input.submit').click(EE.notepad.submit);
			
			notepad_txtarea.autoResize();
		},
		
		submit: function() {
			current_content = $.trim(notepad_txtarea.val());

			var newval = current_content.replace(/</ig, '&lt;').replace(/>/ig, '&gt;').replace(/\n/ig, '<br />');

			notepad_txtarea.attr('readonly', 'readonly').css('opacity', 0.5);
			notepad_controls.find('#notePadSaveIndicator').show();

			$.post(notepad_form.attr('action'), {'notepad': current_content, 'XID': EE.XID }, function(ret) {
				notepad_text.html(newval || notepad_empty).show();
				notepad_txtarea.attr('readonly', '').css('opacity', 1).hide();
				notepad_controls.hide().find('#notePadSaveIndicator').hide();
			}, 'json');
			return false;
		},
		
		show: function() {
			// Already showing?
			if (notepad_controls.is(':visible')) {
				return false;
			}

			var newval = '';

			if (notepad_text.hide().text() != notepad_empty) {
				newval = notepad_text.html().replace(/<br>/ig, '\n').replace(/&lt;/ig, '<').replace(/&gt;/ig, '>');
			}

			notepad_controls.show();
			notepad_txtarea.val(newval).show()
							.height(0).focus()
							.trigger('keypress');
		},
		
		hide: function() {
			notepad_text.show();
			notepad_txtarea.hide();
			notepad_controls.hide();
			return false;
		}
	}
})();

EE.notepad.init();


// Show / hide accessories

$('#accessoryTabs li a').click(function() {
	var parent = $(this).parent("li"),
		accessory = $("#" + this.className);
	
	if (parent.hasClass("current")) {
		accessory.hide();
		parent.removeClass("current");
	}
	else {
		if (parent.siblings().hasClass("current")) {
			accessory.show().siblings(":not(#accessoryTabs)").hide();
			parent.siblings().removeClass("current");
		}
		else {
			accessory.slideDown();
		}
		parent.addClass("current");
	}
	
	return false;
});


// Ajax for control panel search

function control_panel_search() {
	var search = $('#search'),
		result = search.clone(),
		buttonImgs = $('#cp_search_form').find('.searchButton');
	
	submit_handler = function() {
		var url = $(this).attr('action'),
			data = {
				'cp_search_keywords': $('#cp_search_keywords').attr('value')
			};

		$.ajax({
			url: url+'&ajax=y',
			data: data,
			beforeSend: function() {
				buttonImgs.toggle();
			},
			success: function(ret) {
				buttonImgs.toggle();

				search = search.replaceWith(result);
				result.html(ret);

				$('#cp_reset_search').click(function() {
					result = result.replaceWith(search);

					$('#cp_search_form').submit(submit_handler);
					$('#cp_search_keywords').select();
					return false;
				});
			},
			dataType: 'html'
		});

		return false;
	}

	$('#cp_search_form').submit(submit_handler);
}

control_panel_search();


// Setup sidebar hover descriptions

$('h4', '#quickLinks').click(function() {
	window.location.href = EE.BASE+'&C=myaccount&M=quicklinks';
})
.add('#notePad').hover(function() {
	$('.sidebar_hover_desc', this).show();
}, function() {
	$('.sidebar_hover_desc', this).hide();
})
.css('cursor', 'pointer');


// Logout button confirmation

$("#activeUser").one("mouseover", function() {

	var logout_modal = $('<div id="logOutConfirm">'+EE.lang.logout_confirm+' </div>'),
		ttl = 30,
		orig_ttl = ttl,
		countdown_timer;

	function log_me_out() {
		// Won't redirect on unload
		$.ajax({
			url: EE.BASE+"&C=login&M=logout",
			async: ( ! $.browser.safari)
		});

		// Redirect
		window.location=EE.BASE+"&C=login&M=logout";
	}
	
	function delay_logout() {
		if (ttl < 1) {
			return setTimeout(log_me_out, 0);
		}
		else if (ttl == orig_ttl) {
			$(window).bind("unload.logout", log_me_out);
		}
		
		logout_modal.dialog("option", "title", EE.lang.logout+" ("+ (ttl-- || "...") +")");
		countdown_timer = setTimeout(delay_logout, 1000);
	}
	
	function cancel_logout() {
		clearTimeout(countdown_timer);
		$(window).unbind("unload.logout");
		ttl = orig_ttl;
	}
	
	var buttons = {};
		buttons['Cancel'] = function() { $(this).dialog("close"); };
		buttons[EE.lang.logout] = log_me_out;
	
	logout_modal.dialog({
		autoOpen: false,
		resizable: false,
		modal: true,
		title: EE.lang.logout,
		position: "center",
		minHeight: "0px",
		buttons: buttons,
		beforeclose: cancel_logout
	});

	$("a.logOutButton", this).click(function(){
		$("#logOutConfirm").dialog("open");
		$(".ui-dialog-buttonpane button:eq(2)").focus(); //focus on Log-out so pressing return logs out
		
		delay_logout();
		return false;
	});
});

$(".js_show").show();

});