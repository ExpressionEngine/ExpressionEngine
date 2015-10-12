/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */
/**
 * This jQuery plugin handles scaling resize fields when resizing images
 *
 * Example usage:
 *	$('form').resize_scale({
 *		"resize_width": 	"#resize_width",
 *		"resize_height": 	"#resize_height",
 *		"submit_resize": 	"#submit_resize",
 *		"cancel_resize": 	"#cancel_resize",
 *		"default_height": 	$image_height,
 *		"default_width": 	$image_width
 *  });
 */
!function(e){var i={
// Selectors for inputs
resize_width:"#resize_width",resize_height:"#resize_height",submit_resize:"",cancel_resize:"",oversized_class:"oversized",default_height:0,default_width:0,resize_confirm:"",
// Callbacks
callback_resize:"",callback_submit:"",callback_cancel:""};e.fn.resize_scale=function(a){return this.each(function(){var t=e.extend({},i,a),l=e(this),s=e(t.resize_width,l),c=e(t.resize_height,l),h=e(t.submit_resize,l),r=e(t.cancel_resize,l);
// Ensure default height and width are numbers
t.default_height=parseInt(t.default_height,10),t.default_width=parseInt(t.default_width,10),s.add(c).keyup(function(i){
// Enable cancel button
r.show();
// Need to maintain proportions and resize image
// In order to do this, I need to figure out ratio and adhere to it
var a,l=e(this),h=l.attr("id"),d="resize_height"===h?s:c;
// Determine ratio
a="resize_width"===h?t.default_height/t.default_width:t.default_width/t.default_height,
// Change other element's value
d.val(Math.round(a*l.val())),s.val()>t.default_width||c.val()>t.default_height?(s.addClass(t.oversized_class),c.addClass(t.oversized_class)):(c.removeClass(t.oversized_class),s.removeClass(t.oversized_class)),"function"==typeof t.callback_resize&&t.callback_resize.call(this,{width:s.val(),height:c.val()})}),h.off("click","**").on("click",function(i){if(e("."+t.oversized_class).size()){var a=confirm(t.resize_confirm);0==a?i.preventDefault():"function"==typeof t.callback_submit?t.callback_submit.call(this):l.trigger("submit")}}),r.size()&&r.click(function(e){e.preventDefault(),s.val(t.default_width).removeClass(t.oversized_class),c.val(t.default_height).removeClass(t.oversized_class),"function"==typeof t.callback_cancel&&t.callback_cancel.call(this,{width:s.val(),height:c.val()}),r.hide()})})}}(jQuery);