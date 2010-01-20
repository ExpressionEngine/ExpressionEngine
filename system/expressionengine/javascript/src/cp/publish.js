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


//	tab_focus(cur_tab.replace(/menu_/, ""));


	alert(JSON.stringify(layout_object, null, '\t'));

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
			data: "XID="+EE.XID+"&json_tab_layout="+json_tab_layout+"&"+$("#layout_groups_holder input").serialize()+"&channel_id="+EE.publish.channel_id,
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
		data: "XID="+EE.XID+"&json_tab_layout="+json_tab_layout+"&"+$("#layout_groups_holder input").serialize()+"&channel_id='.$channel_id.'&field_group='.$field_group.'",
		success: function(msg){
			$.ee_notice(EE.publish.lang.layout_removed + " <a href=\"javascript:location=location\">"+EE.publish.lang.refresh_layout+"</a>", {duration:0, type:"success"});
		}
	});
}