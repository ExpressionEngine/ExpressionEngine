/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

(function(b){var h={resize_width:"#resize_width",resize_height:"#resize_height",submit_resize:"",cancel_resize:"",oversized_class:"oversized",default_height:0,default_width:0,resize_confirm:"",callback_resize:"",callback_submit:"",callback_cancel:""};b.fn.resize_scale=function(k){return this.each(function(){var a=b.extend({},h,k),d=b(this),c=b(a.resize_width,d),e=b(a.resize_height,d),l=b(a.submit_resize,d),f=b(a.cancel_resize,d);a.default_height=parseInt(a.default_height,10);a.default_width=parseInt(a.default_width,
10);c.add(e).keyup(function(g){f.show();g=b(this);var d=g.attr("id");("resize_height"===d?c:e).val(Math.round(("resize_width"===d?a.default_height/a.default_width:a.default_width/a.default_height)*g.val()));c.val()>a.default_width||e.val()>a.default_height?(c.addClass(a.oversized_class),e.addClass(a.oversized_class)):(e.removeClass(a.oversized_class),c.removeClass(a.oversized_class));"function"===typeof a.callback_resize&&a.callback_resize.call(this,{width:c.val(),height:e.val()})});l.off("click",
"**").on("click",function(c){b("."+a.oversized_class).size()&&(!1==confirm(a.resize_confirm)?c.preventDefault():"function"===typeof a.callback_submit?a.callback_submit.call(this):d.trigger("submit"))});f.size()&&f.click(function(b){b.preventDefault();c.val(a.default_width).removeClass(a.oversized_class);e.val(a.default_height).removeClass(a.oversized_class);"function"===typeof a.callback_cancel&&a.callback_cancel.call(this,{width:c.val(),height:e.val()});f.hide()})})}})(jQuery);
