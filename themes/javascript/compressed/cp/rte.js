(function($){
	
	// Home Page
	
	
	// Toolset Builder
	var
	$selected	= $("#null"),
	$used		= $("#rte-tools-selected").bind( "sortupdate", update_rte_toolset ),
	$unused		= $("#rte-tools-unused");

	$used.add($unused).sortable({
		connectWith:	".rte-tools-connected",
		containment:	".rte-toolset-builder",
		items:			"li:not(.rte-tool-placeholder)",
		opacity:		0.6,
		revert:			.25,
		tolerance:		"pointer"
	});

	$("li[data-tool-id]")
		.hover(
			function(){
				$(this).addClass("rte-tool-hover");
			},
			function(){
				$(this).removeClass("rte-tool-hover");
			}
		)
		.click(function(){
			$selected = $selected.add(
				$(this).addClass("rte-tool-active")
			);
		});

	$("#rte-tools-select").click(function(){
		$unused.find("li.rte-tool-active").appendTo($used);
		update_rte_toolset();
	});
	$("#rte-tools-deselect").click(function(){
		$used.find("li.rte-tool-active").appendTo($unused);
		update_rte_toolset();
	});

	function update_rte_toolset()
	{
		var ids = [];
		$used.find("li[data-tool-id]").each(function(){
			ids.push( $(this).data("tool-id") );
		});
		$("#rte-toolset-tools").val( ids.join("|") );
		$selected.removeClass("rte-tool-active");
		$selected = $("#noyourenevergonnagetit");
	}
	
})(jQuery);