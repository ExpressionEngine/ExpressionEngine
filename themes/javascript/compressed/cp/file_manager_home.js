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

$(document).ready(function(){$("#dir_choice").change(function(){window.location=EE.BASE+"&C=content_files&directory="+$(this).val()});$(".toggle_all").toggle(function(){$(this).closest("table").find("tbody tr").addClass("selected");$(this).closest("table").find("input.toggle").attr("checked",true)},function(){$(this).closest("table").find("tbody tr").removeClass("selected");$(this).closest("table").find("input.toggle").attr("checked",false)});$("input.toggle").each(function(){this.checked=false});
$("a.overlay").live("click",function(){$("#overlay").hide().removeData("overlay");$("#overlay .contentWrap img").remove();$("<img />").appendTo("#overlay .contentWrap").load(function(){var c=$(this).clone().appendTo(document.body).show(),d=c.width(),e=c.height(),b=$(window).width()*0.8,a=$(window).height()*0.8;b=b/d;a=a/e;a=b>a?a:b;c.remove();if(a<1){e*=a;d*=a;$(this).height(e).width(d)}$("#overlay").overlay({load:true,speed:100,top:"center"})}).attr("src",$(this).attr("href"));return false});$("#overlay").css("cursor",
"pointer").click(function(){$(this).fadeOut(100)})});
