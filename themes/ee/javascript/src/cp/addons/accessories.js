
// Za Toggles, zey do nuffink!

$(".toggle_controllers").toggle(
	function(){
		$("input[class=toggle_controller]").each(function() {
			this.checked = true;
		});
	}, function (){
		$("input[class=toggle_controller]").each(function() {
			this.checked = false;
		});
	}
);

$(".toggle_groups").toggle(
	function(){
		$("input[class=toggle_group]").each(function() {
			this.checked = true;
		});
	}, function (){
		$("input[class=toggle_group]").each(function() {
			this.checked = false;
		});
	}
);

// hide sub controllers
var subs = $(".sub_addons, .sub_admin_, .sub_conten, .sub_tools_").hide();

// add plus sign to parent controllers
$(".addons td:first, .admin td:first, .content td:first, .tools td:first").prepend("<img class=\"acc_toggle\" width=\"11\" height=\"10\" src=\"" + EE.THEME_URL + "images/publish_plus.png\" alt=\"\" style=\"float:left;position:absolute;\" />");
subs.find("td.controller_label").css("padding-left", "36px");

$(".acc_toggle").css("cursor", "pointer"); // just styling it like a link

// toggle visible and invisible
$(".acc_toggle").toggle(
	function(){
		var class_name = prep_class($(this));
		$(this).attr("src", EE.THEME_URL +"images/publish_minus.gif");
		$(class_name).each(function() {
			$(this).show();
		});
		table_stripe();
	}, function (){
		var class_name = prep_class($(this));
		$(this).attr("src", EE.THEME_URL + "images/publish_plus.png");
		$(class_name).each(function() {
			$(this).hide();
		});
		table_stripe();
	}
);

// toggle checkboxes for groups
// toggle visible and invisible
$(".addons input, .admin input, .content input, .tools input").click(function(){
	var checked = $(this).attr("checked");
	var class_name = prep_class($(this)) + " input";
	$(class_name).each(function() {
			$(this).attr("checked", checked);
	});
});

function prep_class(obj)
{
	var class_name = $(obj).parent().parent().attr("class").replace(/even/, "").replace(/odd/, "").replace(/ /, "").substring(0,6);
	if (class_name.length < 6)
	{
		class_name += "_";
	}
	return ".sub_"+class_name;
}

function table_stripe()
{
	$("table tbody tr:visible:even").addClass("even");
	$("table tbody tr:visible:odd").addClass("odd");
}

table_stripe();