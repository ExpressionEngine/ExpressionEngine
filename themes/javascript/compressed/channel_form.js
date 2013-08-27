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

var selField=!1,selMode="normal";function setFieldName(c){c!=selField&&(selField=c,clear_state(),tagarray=[],usedarray=[],running=0)}
function taginsert(c,h,e){var f=eval("item.name");if(!selField)return $.ee_notice(no_cursor),!1;var b=!1,d=!1,a=document.getElementById("entryform")[selField];"guided"==selMode&&(data=prompt(enter_text,""),null!=data&&""!=data&&(d=h+data+e));if(document.selection)b=document.selection.createRange().text,a.focus(),b?document.selection.createRange().text=!1==d?h+b+e:d:document.selection.createRange().text=!1==d?h+e:d,a.blur(),a.focus();else if(isNaN(a.selectionEnd)){if("guided"==selMode)curField=document.submit_post[selfField],
curField.value+=d;else{if("other"==c)eval("document.getElementById('entryform')."+selField+".value += tagOpen");else if(0==eval(f))eval("document.getElementById('entryform')."+selField+".value += result"),eval(f+" = 1"),arraypush(tagarray,e),arraypush(usedarray,f),running++,styleswap(f);else{for(i=n=0;i<tagarray.length;i++)if(tagarray[i]==e){n=i;for(running--;tagarray[n];)closeTag=arraypop(tagarray),eval("document.getElementById('entryform')."+selField+".value += closeTag");for(;usedarray[n];)clearState=
arraypop(usedarray),eval(clearState+" = 0"),document.getElementById(clearState).className="htmlButtonA"}0>=running&&"htmlButtonB"==document.getElementById("close_all").className&&(document.getElementById("close_all").className="htmlButtonA")}curField=eval("document.getElementById('entryform')."+selField)}curField.blur();curField.focus()}else{c=a.scrollTop;var g=a.textLength,b=a.selectionStart,k=a.selectionEnd;2>=k&&"undefined"!=typeof g&&(k=g);f=a.value.substring(0,b);g=a.value.substring(b,k).s3=
a.value.substring(k,g);!1==d?(b=b+h.length+g.length+e.length,a.value=!1==d?f+h+g+e+s3:d):(b+=d.length,a.value=f+d+s3);a.focus();a.selectionStart=b;a.selectionEnd=b;a.scrollTop=c}}
$(document).ready(function(){$(".js_show").show();$(".js_hide").hide();void 0!==EE.publish.markitup&&void 0!==EE.publish.markitup.fields&&$.each(EE.publish.markitup.fields,function(c,e){$("#"+c).markItUp(mySettings)});!0===EE.publish.smileys&&($("a.glossary_link").click(function(){$(this).parent().siblings(".glossary_content").slideToggle("fast");$(this).parent().siblings(".smileyContent .spellcheck_content").hide();return!1}),$("a.smiley_link").toggle(function(){which=$(this).attr("id").substr(12);
$("#smiley_table_"+which).slideDown("fast",function(){$(this).css("display","")})},function(){$("#smiley_table_"+which).slideUp("fast")}),$(this).parent().siblings(".glossary_content, .spellcheck_content").hide(),$(".glossary_content a").click(function(){$.markItUp({replaceWith:$(this).attr("title")});return!1}));$(".btn_plus a").click(function(){return confirm(EE.lang.confirm_exit,"")});$(".markItUpHeader ul").prepend('<li class="close_formatting_buttons"><a href="#"><img width="10" height="10" src="'+
EE.THEME_URL+'images/publish_minus.gif" alt="Close Formatting Buttons"/></a></li>');$(".close_formatting_buttons a").toggle(function(){$(this).parent().parent().children(":not(.close_formatting_buttons)").hide();$(this).parent().parent().css("height","13px");$(this).children("img").attr("src",EE.THEME_URL+"images/publish_plus.png")},function(){$(this).parent().parent().children().show();$(this).parent().parent().css("height","auto");$(this).children("img").attr("src",EE.THEME_URL+"images/publish_minus.gif")});
var c="";!0===EE.publish.show_write_mode&&$("#write_mode_textarea").markItUp(myWritemodeSettings);$(".write_mode_trigger").click(function(){c=$(this).attr("id").match(/^id_\d+$/)?"field_"+$(this).attr("id"):$(this).attr("id").replace(/id_/,"");$("#write_mode_textarea").val($("#"+c).val());$("#write_mode_textarea").focus();return!1})});
