/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */
$(document).ready(function(){function e(t,i,a){$("div.box",t).html(i),EE.cp.formValidation.init(t),$("input[name=cat_name]",t).bind("keyup keydown",function(){$(this).ee_url_title("input[name=cat_url_title]")}),$("form",t).on("submit",function(){return $.ajax({type:"POST",url:this.action,data:$(this).add('input[name="categories[]"]').serialize()+"&save_modal=yes",dataType:"json",success:function(i){"success"==i.messageType?(t.trigger("modal:close"),a.parents("fieldset").find(".setting-field").html(i.body)):e(t,i.body,a)}}),!1})}var t=$("div.publish form");
// Autosaving
if(1==EE.publish.title_focus&&$("div.publish form input[name=title]").focus(),"new"==EE.publish.which&&$("div.publish form input[name=title]").bind("keyup blur",function(){$("div.publish form input[name=title]").ee_url_title($("div.publish form input[name=url_title]"))}),
// Emoji
EE.publish.smileys===!0&&$(".format-options .toolbar .emoji a").click(function(e){$(this).parents(".format-options").find(".emoji-wrap").slideToggle("fast"),e.preventDefault()}),EE.publish.autosave&&EE.publish.autosave.interval){var i=!1;t.on("entry:startAutosave",function(){t.trigger("entry:autosave"),i||(i=!0,setTimeout(function(){$.ajax({type:"POST",dataType:"json",url:EE.publish.autosave.URL,data:t.serialize(),success:function(e){t.find("div.alert.inline.warn").remove(),e.error?console.log(e.error):e.success?t.prepend(e.success):console.log("Autosave Failed"),i=!1}})},1e3*EE.publish.autosave.interval))});
// Start autosave when something changes
var a=$("textarea, input").not(":password,:checkbox,:radio,:submit,:button,:hidden"),n=$("select, :checkbox, :radio, :file");a.on("keypress change",function(){t.trigger("entry:startAutosave")}),n.on("change",function(){t.trigger("entry:startAutosave")})}
// Category modal
$("a[rel=modal-add-category]").click(function(t){console.log($('input[name="categories[]"]').serialize());var i=$(this),a=i.attr("rel");$.ajax({type:"GET",url:EE.publish.add_category.URL.replace("###",$(this).data("catGroup")),dataType:"html",success:function(t){var n=$("."+a);e(n,t,i)}})})});