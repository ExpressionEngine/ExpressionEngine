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

$("table").each(function(){var a;$(this).data("table_config")&&(a=$(this).data("table_config"),$.isPlainObject(a)||(a=$.parseJSON(a)),$(this).table(a));jQuery().toggle_all&&$(this).toggle_all()});
$(function(){function a(){var a=EE.SESS_TIMEOUT-6E4,c=EE.XID_TIMEOUT-6E4,e=a<c?a:c,f=!1,g,d;d=function(){$.ajax({type:"POST",dataType:"json",url:EE.BASE+"&C=login&M=refresh_xid",success:function(a){$("input[name='XID']").val(a.xid);EE.XID=a.xid;setTimeout(d,c)}})};g=function(){var a='<form><div id="logOutWarning" style="text-align:center"><p>'+EE.lang.session_expiring+'</p><label for="username">'+EE.lang.username+'</label>: <input type="text" id="log_backin_username" name="username" value="" style="width:100px" size="35" dir="ltr" id="username" maxlength="50"  />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<label for="password">'+
EE.lang.password+'</label>: <input class="field" id="log_backin_password" type="password" name="password" value="" style="width:100px" size="32" dir="ltr" id="password" maxlength="32"  />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" id="submit" name="submit" value="'+EE.lang.login+'" class="submit" /><span id="logInSpinner"></span></div></form>',b;if(!0===f)return l(f),!1;setTimeout(l,e);$.ee_notice(a,{type:"custom",open:!0,close_on_click:!1});b=$("#logOutWarning");b.find("#log_backin_username").focus();
b.find("input#submit").click(function(){var a=b.find("input#log_backin_username").val(),d=b.find("input#log_backin_password").val(),c=$(this),h=b.find("span#logInSpinner");c.hide();h.html('<img src="'+EE.PATH_CP_GBL_IMG+'loader_blackbg.gif" />');$.ajax({type:"POST",dataType:"json",url:EE.BASE+"&C=login&M=authenticate&is_ajax=true",data:{username:a,password:d,XID:EE.XID},success:function(a){f=!0;"success"===a.messageType?($("input[name='XID']").val(a.xid),b.slideUp("fast"),$.ee_notice(a.message,{type:"custom",
open:!0}),setTimeout($.ee_notice.destroy,1600),EE.XID=a.xid,f=!0,clearTimeout(g),setTimeout(g,e)):"failure"===a.messageType?(b.before('<div id="loginCheckFailure">'+a.message+"</div>"),h.hide("fast"),c.css("display","inline")):"logout"===a.messageType&&(window.location.href=EE.BASE+"&C=login&M=logout&auto_expire=true")}});return!1})};"c"===EE.SESS_TYPE?setTimeout(d,c):setTimeout(g,e)}var l=function(h){var c=$('<div id="logOutConfirm">'+EE.lang.session_timeout+" </div>"),e=30,f=e,g,d,k;d=function(){window.location=
EE.BASE+"&C=login&M=logout&auto_expire=true"};k=function(){if(1>e)return setTimeout(d,0);e===f&&$(window).bind("unload.logout",d);c.dialog("option","title",EE.lang.logout+" ("+(e--||"...")+")");g=setTimeout(k,1E3)};h={Cancel:function(){$(this).dialog("close")}};h[EE.lang.logout]=d;c.dialog({autoOpen:!1,resizable:!1,modal:!0,title:EE.lang.logout,position:"center",minHeight:"0",buttons:h,beforeClose:function(){clearTimeout(g);$(window).unbind("unload.logout");e=f;$.ajax({type:"POST",dataType:"json",
url:EE.BASE+"&C=login&M=refresh_xid",success:function(b){$("input[name='XID']").val(b.xid);EE.XID=b.xid;$("#logOutWarning").slideUp("fast");a()}})}});$("#logOutConfirm").dialog("open");$(".ui-dialog-buttonpane button:eq(2)").focus();k();return!1};EE.SESS_TIMEOUT&&a()});
