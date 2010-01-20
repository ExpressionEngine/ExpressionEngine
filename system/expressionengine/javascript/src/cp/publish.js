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
		$(this).find(".cp_button a").corner();
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
	for (var i in cat_groups_containers)
	{
		cat_groups_containers[i].find("a").live("click", reload);
	}


	// Last but not least - update the checkboxes
	$("a#refresh_categories", "#sub_hold_field_category").live("click", function() {
		var that = $(this).hide().nextAll("div");
		that.text("loading...").load(EE.BASE+"&C=content_publish&M=ajax_update_cat_fields&group_id="+that.data("gid")+"&timestamp="+now());
		return false;
	});
}