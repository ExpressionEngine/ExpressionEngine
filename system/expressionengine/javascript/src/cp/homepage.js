$(document).ready(function() {

	var ajaxContentButtons = {};
		ajaxContentButtons[EE.lang.close] = $(this).dialog("close");	
	
	$("<div id=\"ajaxContent\"></div>").dialog({
		autoOpen: false,
		resizable: false,
		modal: true,
		position: "center",
		minHeight: "0px", // fix display bug, where the height of the dialog is too big
		buttons: ajaxContentButtons
	});

	$("a.submenu").click(function() {
		if ($(this).data("working")) {
			return false;
		}
		else {
			$(this).data("working", true);
		}
		
		var url = $(this).attr("href"),
			that = $(this).parent(),
			submenu = that.find("ul");

		if ($(this).hasClass("accordion")) {
			
			if (submenu.length > 0) {
				if ( ! that.hasClass("open")) {
					that.siblings(".open").toggleClass("open").children("ul").slideUp("fast");
				}

				submenu.slideToggle("fast");
				that.toggleClass("open");
			}
			
			$(this).data("working", false);
		}
		else {
			$(this).data("working", false);
			var dialog_title = $(this).html();

			$("#ajaxContent").load(url+" .pageContents", function() {
				$("#ajaxContent").dialog("option", "title", dialog_title);
				$("#ajaxContent").dialog("open");
			});
		}

		return false;
	});


	if (EE.importantMessage) {
		var msgBoxOpen = EE.importantMessage.state,
			msgContainer = $("#ee_important_message");			
	
		function save_state() {
			msgBoxOpen = ! msgBoxOpen;
			document.cookie="exp_home_msg_state="+(msgBoxOpen ? "open" : "closed");
		}
	
		function setup_hidden() {
			$.ee_notice.show_info(function() {
				$.ee_notice.hide_info();
				msgContainer.removeClass("closed").show();
				save_state();
			});
		}
	
		msgContainer.find(".msg_open_close").click(function() {
			msgContainer.hide();
			setup_hidden();
			save_state();
		});
	
		if ( ! msgBoxOpen) {
			setup_hidden();
		}		
	}
});
