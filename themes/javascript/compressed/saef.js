/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

var selField=!1,selMode="normal";function setFieldName(b){b!=selField&&(selField=b,clear_state(),tagarray=[],usedarray=[],running=0)}
function taginsert(b,h,e){var f=eval("item.name");if(!selField)return $.ee_notice(no_cursor),!1;var c=!1,d=!1,a=document.getElementById("entryform")[selField];selMode=="guided"&&(data=prompt(enter_text,""),data!=null&&data!=""&&(d=h+data+e));if(document.selection)c=document.selection.createRange().text,a.focus(),c?document.selection.createRange().text=d==!1?h+c+e:d:document.selection.createRange().text=d==!1?h+e:d,a.blur(),a.focus();else if(isNaN(a.selectionEnd)){if(selMode=="guided")curField=document.submit_post[selfField],
curField.value+=d;else{if(b=="other")eval("document.getElementById('entryform')."+selField+".value += tagOpen");else if(eval(f)==0)eval("document.getElementById('entryform')."+selField+".value += result"),eval(f+" = 1"),arraypush(tagarray,e),arraypush(usedarray,f),running++,styleswap(f);else{for(i=n=0;i<tagarray.length;i++)if(tagarray[i]==e){n=i;for(running--;tagarray[n];)closeTag=arraypop(tagarray),eval("document.getElementById('entryform')."+selField+".value += closeTag");for(;usedarray[n];)clearState=
arraypop(usedarray),eval(clearState+" = 0"),document.getElementById(clearState).className="htmlButtonA"}if(running<=0&&document.getElementById("close_all").className=="htmlButtonB")document.getElementById("close_all").className="htmlButtonA"}curField=eval("document.getElementById('entryform')."+selField)}curField.blur();curField.focus()}else{var b=a.scrollTop,g=a.textLength,c=a.selectionStart,j=a.selectionEnd;j<=2&&typeof g!="undefined"&&(j=g);f=a.value.substring(0,c);g=a.value.substring(c,j).s3=
a.value.substring(j,g);d==!1?(c=c+h.length+g.length+e.length,a.value=d==!1?f+h+g+e+s3:d):(c+=d.length,a.value=f+d+s3);a.focus();a.selectionStart=c;a.selectionEnd=c;a.scrollTop=b}}
$(document).ready(function(){$(".js_show").show();$(".js_hide").hide();EE.publish.markitup!==void 0&&EE.publish.markitup.fields!==void 0&&$.each(EE.publish.markitup.fields,function(b){$("#"+b).markItUp(mySettings)});EE.publish.smileys===!0&&($("a.glossary_link").click(function(){$(this).parent().siblings(".glossary_content").slideToggle("fast");$(this).parent().siblings(".smileyContent .spellcheck_content").hide();return!1}),$("a.smiley_link").toggle(function(){which=$(this).attr("id").substr(12);
$("#smiley_table_"+which).slideDown("fast",function(){$(this).css("display","")})},function(){$("#smiley_table_"+which).slideUp("fast")}),$(this).parent().siblings(".glossary_content, .spellcheck_content").hide(),$(".glossary_content a").click(function(){$.markItUp({replaceWith:$(this).attr("title")});return!1}));$(".btn_plus a").click(function(){return confirm(EE.lang.confirm_exit,"")});$(".markItUpHeader ul").prepend('<li class="close_formatting_buttons"><a href="#"><img width="10" height="10" src="'+
EE.THEME_URL+'images/publish_minus.gif" alt="Close Formatting Buttons"/></a></li>');$(".close_formatting_buttons a").toggle(function(){$(this).parent().parent().children(":not(.close_formatting_buttons)").hide();$(this).parent().parent().css("height","13px");$(this).children("img").attr("src",EE.THEME_URL+"images/publish_plus.png")},function(){$(this).parent().parent().children().show();$(this).parent().parent().css("height","auto");$(this).children("img").attr("src",EE.THEME_URL+"images/publish_minus.gif")});
var b="";EE.publish.show_write_mode===!0&&$("#write_mode_textarea").markItUp(myWritemodeSettings);$(".write_mode_trigger").click(function(){b=$(this).attr("id").match(/^id_\d+$/)?"field_"+$(this).attr("id"):$(this).attr("id").replace(/id_/,"");$("#write_mode_textarea").val($("#"+b).val());$("#write_mode_textarea").focus();return!1})});
