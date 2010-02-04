EE.publish = EE.publish || {};

// The functions in this file are called from within publish if their components
// are needed. So for example EE.publish.category_editor() is called after
// the category menu is constructed.

EE.publish.category_editor = function() {
	var cat_groups = [],
		cat_groups_containers = {},
		cat_list_url = EE.BASE+'&C=admin_content&M=category_editor&group_id=';

	// IE caches $.load requests, so we need a unique number
	function now() {
		return +new Date;
	}

	// Grab all group ids
	$(".edit_categories_link").each(function() {
		var gid = this.href.substr(this.href.lastIndexOf("=") + 1);
		$(this).data("gid", gid);
		cat_groups.push(gid);
	});

	for(i = 0; i < cat_groups.length; i++) {
		cat_groups_containers[cat_groups[i]] = $("#cat_group_container_"+[cat_groups[i]]);
		cat_groups_containers[cat_groups[i]].data("gid", cat_groups[i]);
	}
	
	refresh_cats = function(gid) {
		cat_groups_containers[gid].text("loading...").load(cat_list_url+gid+"&timestamp="+now()+" .pageContents", function(res) {
			setup_page(res, false);
		});
	}

	// A function to setup new page events
	setup_page = function(response, require_valid_response) {
		if (response[0] != '<' && require_valid_response) {
			return refresh_cats( $(this).closest(".cat_group_container").data("gid") );
		}
		
		var container = $(this);
	
		container.parent().find("#refresh_categories").show();
		container.find("form").submit(function() {
			var that = $(this),
				values = that.serialize(),
				url = that.attr("action");

			$.ajax({
				url: url,
				type: "POST",
				data: values,
				dataType: "html",
				beforeSend: function() {
					container.html("loading...");
				},
				success: function(res) {
					// A bit hacky, but it works - trigger our live event
					container.html($(res).find(".pageContents"));
					setup_page.call(container);
				}
			});

			return false;
		});
		
		return false;
	}

	// And a function to do the work
	function reload() {
		var gid = $(this).data("gid");

		if ( ! gid) {
			gid = $(this).closest(".cat_group_container").data("gid");
		}

		cat_groups_containers[gid].text("loading...").load(this.href+"&modal=yes&timestamp="+now()+" .pageContents", setup_page);
		return false;
	}

	// Hijack edit category links to get it off the ground
	$(".edit_categories_link").click(reload);
	

	// Bind the live events for internal links
	$.each(cat_groups_containers, function() {
		this.find("a").live("click", reload);
	});


	// Last but not least - update the checkboxes
	$("a#refresh_categories", "#sub_hold_field_category").live("click", function() {
		var that = $(this).hide().nextAll("div");
		that.text("loading...").load(EE.BASE+"&C=content_publish&M=ajax_update_cat_fields&group_id="+that.data("gid")+"&timestamp="+now());
		return false;
	});
}


EE.publish.save_layout = function() {
	
	var tab_count = 0,
		layout_object = {},
		cur_tab			= $("#tab_menu_tabs li.current").attr("id");

	// for width() to work, the element cannot be in a parent div that is display:none
	$(".main_tab").show();

	$("li:visible", "#tab_menu_tabs").each(function() {

		// skip list items with no id (ie: new tab)
		if (this.id && this.id != "")
		{
			var tab_name = this.id.replace(/menu_/, "");
			
			layout_object[tab_name] = {};

			$("#"+tab_name).find(".publish_field").each(function() {

					var that = $(this);
						id = this.id.replace(/hold_field_/, ""),
						percent_width = Math.round((that.width() / that.parent().width()) * 10) * 10,
						temp_buttons = $("#sub_hold_field_"+id+" .markItUp ul li:eq(2)");
						
					if (temp_buttons.html() != "undefined" && temp_buttons.css("display") != "none") {
						temp_buttons = true;
					}
					else {
						temp_buttons = false;
					}
					
					layout_object[tab_name][id] = {
						visible		: ($(this).css("display") == "none") ? false : true,
						collapse	: ($("#sub_hold_field_"+id).css("display") == "none") ? true : false,
						htmlbuttons	: temp_buttons,
						width		: percent_width+'%'
					};
			});
			
			tab_count++; // add one to the tab count
		}
	});

	//	alert(JSON.stringify(layout_object, null, '\t'));

	// @todo not a great solution
	EE.tab_focus(cur_tab.replace(/menu_/, ""));

	if (tab_count == 0) {
		$.ee_notice(EE.publish.lang.tab_count_zero, {"type" : "error"});
	}
	else if ($("#layout_groups_holder input:checked").length == 0) {
		$.ee_notice(EE.publish.lang.no_member_groups, {"type" : "error"});
	}
	else {
		$.ajax({
			type: "POST",
			url: EE.BASE+"&C=content_publish&M=save_layout",
			data: "XID="+EE.XID+"&json_tab_layout="+JSON.stringify(layout_object)+"&"+$("#layout_groups_holder input").serialize()+"&channel_id="+EE.publish.channel_id,
			success: function(msg){
				$.ee_notice(msg, {type: "success"});
			}
		});
	}
}


EE.publish.remove_layout = function() {
	if ($("#layout_groups_holder input:checked").length == 0) {
		return $.ee_notice(EE.publish.lang.no_member_groups, {"type" : "error"});
	}

	var json_tab_layout = "{}"; // empty array will remove everything nicely

	$.ajax({
		type: "POST",
		url: EE.BASE+"&C=content_publish&M=save_layout",
		data: "XID="+EE.XID+"&json_tab_layout="+json_tab_layout+"&"+$("#layout_groups_holder input").serialize()+"&channel_id="+EE.publish.channel_id+"&field_group="+EE.publish.field_group,
		success: function(msg){
			$.ee_notice(EE.publish.lang.layout_removed + " <a href=\"javascript:location=location\">"+EE.publish.lang.refresh_layout+"</a>", {duration:0, type:"success"});
		}
	});
}

$(document).ready(function() {
	var date_obj = new Date(),
		date_obj_hours = date_obj.getHours(),
		date_obj_mins = date_obj.getMinutes();

	if (date_obj_mins < 10) { date_obj_mins = "0" + date_obj_mins; }

	if (date_obj_hours > 11) {
		date_obj_hours = date_obj_hours - 12;
		date_obj_am_pm = " PM";
	} else {
		date_obj_am_pm = " AM";
	}

	EE.date_obj_time = " '"+date_obj_hours+":"+date_obj_mins+date_obj_am_pm+"'";

	$("#layout_group_submit").click(function(){
		EE.publish.save_layout()
		return false;
	});

	$("#layout_group_remove").click(function(){
		EE.publish.remove_layout()
		return false;
	});

	$(".add_author_link").click(function(){
		$("#add_author_dialog").dialog("open")
		return false;
	});

	function removeAuthor(e)
	{
		$.get(EE.BASE+"&C=content_publish&M=remove_author", { mid: e.attr("id")});
		e.parent().fadeOut();
		// rebuild author table
		$.ajax({
			type: "POST",
			url: EE.BASE+"&C=content_publish&M=build_author_table",
			data: "is_ajax=true"+$("#publishForm").serialize(),
			success: function(result){
				$("#authorsForm").html(result);
				updateAuthorTable();
			}
		});
	}

	$("#author_list_sidebar .delete").click(function(){
		removeAuthor($(this));
		return false;
	});

	$("a.reveal_formatting_buttons").click(function(){
		$(this).parent().parent().children('.close_container').slideDown(); $(this).hide();
		return false;
	});

	$("#write_mode_header .reveal_formatting_buttons").hide();
	$("#write_mode_writer").corner("15px");
	$("#holder").corner("bottom-left");


	if (EE.publish.smileys == 'true') {
		$("a.glossary_link").click(function(){
			$(this).parent().siblings('.glossary_content').slideToggle("fast");$(this).parent().siblings('.smileyContent .spellcheck_content').hide();
			return false;
		});


		$('a.smiley_link').toggle(function() {
			$(this).parent().siblings('.smileyContent').slideDown('fast', function() { $(this).css('display', ''); });
		}, function() {
			$(this).parent().siblings('.smileyContent').slideUp('fast');
		});

		$(this).parent().siblings('.glossary_content, .spellcheck_content').hide();

		$('.glossary_content a').click(function(){
			$.markItUp({ replaceWith:$(this).attr('title')} );
			return false;
		});
	}

	if (EE.publish.autosave) {
		function autosave_entry()
		{
			// If the sidebar is showing, then form fields are disabled. Thus, enable all form elements,
			// grab the data and re-disable (re-dis-able... does not feel like a word) them.
			if ($("#tools:visible").length == 1)
			{
				disable_fields(true);
			}
			
			var form_data = $("#publishForm").serialize();
			
			if ($("#tools:visible").length == 1)
			{
				disable_fields(false);
			}					

			$.ajax({
				type: "POST",
				url: EE.BASE+"&C=content_publish&M=autosave_entry",
				data: form_data,
				success: function(result){
												
					if (isNaN(result))
					{
						$.ee_notice(result, {type:"error"});
					}
					else
					{
						$.ee_notice(EE.publish.autosave.success, {type:"success"});
					}
				}
			});
		}
		// setInterval("autosave_entry();", 1000 * EE.publish.autosave.success); // 1000 milliseconds per second
	}

	$(".markItUp ul").append("<li class=\"btn_plus\"><a title=\""+EE.lang.add_new_html_button+"\" href=\""+EE.BASE+"&C=myaccount&M=html_buttons&id="+EE.user_id+"\">+</a></li>");
	$(".btn_plus a").click(function(){
		return confirm(EE.lang.confirm_exit, "");
	});

	// inject the collapse button into the formatting buttons list
	$(".markItUpHeader ul").prepend("<li class=\"close_formatting_buttons\"><a href=\"#\"><img width=\"10\" height=\"10\" src=\""+EE.THEME_URL+"images/publish_minus.gif\" alt=\"Close Formatting Buttons\"/></a></li>");

	$(".close_formatting_buttons a").toggle(
		function() {
			$(this).parent().parent().children(":not(.close_formatting_buttons)").hide();
			$(this).parent().parent().css("height", "13px");
			$(this).children("img").attr("src", EE.THEME_URL+"images/publish_plus.png");
		}, function () {
			$(this).parent().parent().children().show();
			$(this).parent().parent().css("height", "22px");
			$(this).children("img").attr("src", EE.THEME_URL+"images/publish_minus.gif");
		}
	);

	// Pages URI Placeholder
	if (EE.publish.pages) {
		var pagesUri 		= $("#pages_uri"),
			placeholderText = EE.publish.pages.pagesUri;

		if ( ! pagesUri.value) {
			pagesUri.val(placeholderText);
		}

		pagesUri.focus(function() {					
			if (this.value == placeholderText) {
				$(this).val("");
			}	
		}).blur(function() {
			if (this.value == "") {
				$(this).val(placeholderText);
			}
		});		
	}
	

});


file_manager_context = "";	// @todo - yuck, should be on the EE global
function disable_fields(state)
{
	if (state)
	{
		$(".main_tab input, .main_tab textarea, .main_tab select, #submit_button").attr("disabled", true);
		$("#submit_button").addClass("disabled_field");
		$("#holder a").addClass("admin_mode");
		$("#holder div.markItUp, #holder p.spellcheck").each(function() {
			$(this).before("<div class=\"cover\" style=\"position:absolute;width:100%;height:50px;z-index:9999;\"></div>").css({});
		});
	}
	else
	{
		$(".main_tab input, .main_tab textarea, .main_tab select, #submit_button").removeAttr("disabled");
		$("#submit_button").removeClass("disabled_field");
		$("#holder a").removeClass("admin_mode");
		$(".cover").remove();
	}
}

function removeAuthor(e)
{
	$.get(EE.BASE + "&C=content_publish&M=remove_author", { mid: e.attr("id")});
	e.parent().fadeOut();
}

function updateAuthorTable()
{
	$.ajax({
		type: "POST",
		url: EE.BASE + "&C=content_publish&M=build_author_table",
		data: "XID=" + EE.XID + "&is_ajax=true",
		success: function(e){
			$("#authorsForm").html(e);
		}
	});

	$(".add_author_modal").bind("click", function(e){
		add_authors_sidebar(this);
	});
}

function add_authors_sidebar(e)
{
	var author_id = $(e).attr("id").substring(16);

	$.ajax({
		type: "POST",
		url: EE.BASE + "&C=content_publish&M=build_author_sidebar",
		data: "XID=" + EE.XID + "&author_id="+author_id,
		success: function(e){
			$("#author_list_sidebar").append(e).fadeIn();
			updateAuthorTable();
		}
	});
}

  /** ------------------------------------
  /**  Live URL Title Function
  /** -------------------------------------*/

  function liveUrlTitle()
  {
	var defaultTitle = '',
		newText = document.getElementById("title").value;

	if (defaultTitle != '')
	{
		if (newText.substr(0, defaultTitle.length) == defaultTitle)
		{
			newText = newText.substr(defaultTitle.length);
		}
	}

	newText = newText.toLowerCase();
	var separator = EE.publish.word_separator;

	if (separator != "_")
	{
		newText = newText.replace(/\_/g, separator);
	}
	else
	{
		newText = newText.replace(/\-/g, separator);
	}

	// Foreign Character Attempt

	var newTextTemp = '';
	for(var pos=0; pos<newText.length; pos++)
	{
		var c = newText.charCodeAt(pos);

		if (c >= 32 && c < 128)
		{
			newTextTemp += newText.charAt(pos);
		}
		else
		{
			if (c in EE.publish.foreignChars) {
				newTextTemp += EE.publish.foreignChars[c];
			}
		}
	}

	var multiReg = new RegExp(separator + '{2,}', 'g');

	newText = newTextTemp;

	newText = newText.replace('/<(.*?)>/g', '');
	newText = newText.replace(/\s+/g, separator);
	newText = newText.replace(/\//g, separator);
	newText = newText.replace(/[^a-z0-9\-\._]/g,'');
	newText = newText.replace(/\+/g, separator);
	newText = newText.replace(multiReg, separator);
	newText = newText.replace(/^[-_]|[-_]$/g, '');
	newText = newText.replace(/\.+$/g,'');

	if (document.getElementById("url_title"))
	{
		document.getElementById("url_title").value = "" + newText;
	}
}

var selField  = false,
	selMode = "normal";

//	Dynamically set the textarea name

function setFieldName(which)
{
	if (which != selField)
	{
		selField = which;

		clear_state();

		tagarray  = new Array();
		usedarray = new Array();
		running	  = 0;
	}
}

// Insert tag
function taginsert(item, tagOpen, tagClose)
{
	// Determine which tag we are dealing with

	var which = eval('item.name');

	if ( ! selField)
	{
		$.ee_notice(no_cursor);
		return false;
	}

	var theSelection	= false,
		result			= false,
		theField		= document.getElementById('entryform')[selField];

	if (selMode == 'guided')
	{
		data = prompt(enter_text, "");

		if ((data != null) && (data != ""))
		{
			result =  tagOpen + data + tagClose;
		}
	}

	// Is this a Windows user?
	// If so, add tags around selection

	if (document.selection)
	{
		theSelection = document.selection.createRange().text;

		theField.focus();

		if (theSelection)
		{
			document.selection.createRange().text = (result == false) ? tagOpen + theSelection + tagClose : result;
		}
		else
		{
			document.selection.createRange().text = (result == false) ? tagOpen + tagClose : result;
		}

		theSelection = '';

		theField.blur();
		theField.focus();

		return;
	}
	else if ( ! isNaN(theField.selectionEnd))
	{
		var newStart,
			scrollPos = theField.scrollTop,
			selLength = theField.textLength,
			selStart = theField.selectionStart,
			selEnd = theField.selectionEnd;
			
		if (selEnd <= 2 && typeof(selLength) != 'undefined')
			selEnd = selLength;

		var s1 = (theField.value).substring(0,selStart);
		var s2 = (theField.value).substring(selStart, selEnd)
		var s3 = (theField.value).substring(selEnd, selLength);

		if (result == false)
		{
			newStart = selStart + tagOpen.length + s2.length + tagClose.length;
			theField.value = (result == false) ? s1 + tagOpen + s2 + tagClose + s3 : result;
		}
		else
		{
			newStart = selStart + result.length;
			theField.value = s1 + result + s3;
		}

		theField.focus();
		theField.selectionStart = newStart;
		theField.selectionEnd = newStart;
		theField.scrollTop = scrollPos;
		return;
	}
	else if (selMode == 'guided')
	{
		curField = document.submit_post[selfField];
		
		curField.value += result;
		curField.blur();
		curField.focus();

		return;
	}

	// Add single open tags

	if (item == 'other')
	{
		eval("document.getElementById('entryform')." + selField + ".value += tagOpen");
	}
	else if (eval(which) == 0)
	{
		var result = tagOpen;

		eval("document.getElementById('entryform')." + selField + ".value += result");
		eval(which + " = 1");

		arraypush(tagarray, tagClose);
		arraypush(usedarray, which);

		running++;

		styleswap(which);
	}
	else
	{
		// Close tags

		n = 0;

		for (i = 0 ; i < tagarray.length; i++ )
		{
			if (tagarray[i] == tagClose)
			{
				n = i;

				running--;

				while (tagarray[n])
				{
					closeTag = arraypop(tagarray);
					eval("document.getElementById('entryform')." + selField + ".value += closeTag");
				}

				while (usedarray[n])
				{
					clearState = arraypop(usedarray);
					eval(clearState + " = 0");
					document.getElementById(clearState).className = 'htmlButtonA';
				}
			}
		}

		if (running <= 0 && document.getElementById('close_all').className == 'htmlButtonB')
		{
			document.getElementById('close_all').className = 'htmlButtonA';
		}

	}

	curField = eval("document.getElementById('entryform')." + selField);
	curField.blur();
	curField.focus();
}