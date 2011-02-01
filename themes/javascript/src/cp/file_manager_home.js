/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

/*jslint browser: true, onevar: true, undef: true, nomen: true, eqeqeq: true, plusplus: true, bitwise: true, regexp: false, strict: true, newcap: true, immed: true */

/*global $, jQuery, EE, window, document, console, alert */


//"use strict";

$(document).ready(function () {

	$("#dir_choice").change(function() {
		window.location = EE.BASE+'&C=content_files&directory='+$(this).val();
	});
		
	

	function show_image() {
		// Destroy any existing overlay
		$('#overlay').hide().removeData('overlay');
		$('#overlay .contentWrap img').remove();
		
		// Launch overlay once image finishes loading
		$('<img />').appendTo('#overlay .contentWrap').load(function() {
			
			// We need to scale very large images down just a bit. To do that we
			// need a reference element that we can set to visible very briefly
			// or we won't get a proper width / height
			var ref = $(this).clone().appendTo(document.body).show(),
			
				w = ref.width(),
				h = ref.height(),
				
				max_w = $(window).width() * 0.8,			// 10% margin
				max_h = $(window).height() * 0.8,
				
				rat_w = max_w / w,							// ratios
				rat_h = max_h / h,
				
				ratio = (rat_w > rat_h) ? rat_h : rat_w;	// use the smaller
			
			ref.remove();
			
			// We only scale down - up would be silly
			if (ratio < 1) {
				h = h * ratio;
				w = w * ratio;
				
				$(this).height(h).width(w);
			}
								
			$('#overlay').overlay({
				load: true,
				speed: 100,
				top: 'center'
			});
		})
		
		.attr('src', $(this).attr('href')); // start loading

		// Prevent default click event
		return false;
	}
	
	$(".toggle_all").toggle(
		function () {
			$(this).closest("table").find("tbody tr").addClass("selected");
			$(this).closest("table").find("input.toggle").attr('checked', true);
		}, function () {
			$(this).closest("table").find("tbody tr").removeClass("selected");
			$(this).closest("table").find("input.toggle").attr('checked', false);
		}
	);

	$("input.toggle").each(function () {
		this.checked = false;
	});


	//setup_events();
	
	// Set up image viewer (overlay)
	$('a.overlay').live('click', show_image);
	$('#overlay').css('cursor', 'pointer').click(function() {
		$(this).fadeOut(100);
	});
	
});	
