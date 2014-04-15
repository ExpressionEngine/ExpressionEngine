/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */
$(document).ready(function(){$.ee_watermark()}),$.ee_watermark=function(){var e=$('input[name="wm_type"]:checked').val();"text"==e?$(".image_type").hide():$(".text_type").hide(),$.ee_watermark.type_toggle()},$.ee_watermark.type_toggle=function(){$("input[name=wm_type]").change(function(){$(".text_type").toggle(),$(".image_type").toggle()})},$.ee_watermark.watermark_test=function(){var e="<?php echo $basepath; ?>&P=wm_tester",_=document.forms[0],a="",l=_.gallery_wm_use_font[0].checked?"y":"n",t=_.gallery_wm_use_drop_shadow[0].checked?"y":"n",r=_.gallery_wm_font_color.value,g=_.gallery_wm_shadow_color.value;_.gallery_wm_type[1].checked?a="t":_.gallery_wm_type[2].checked&&(a="g");var m=_.gallery_wm_text.value;m=m.replace("/;/g","").replace("?","");var w=e+"&gallery_wm_type="+a+"&gallery_wm_text="+m+"&gallery_wm_image_path="+_.gallery_wm_image_path.value+"&gallery_wm_use_font="+l+"&gallery_wm_font="+_.gallery_wm_font.value+"&gallery_wm_font_size="+_.gallery_wm_font_size.value+"&gallery_wm_vrt_alignment="+_.gallery_wm_vrt_alignment.value+"&gallery_wm_hor_alignment="+_.gallery_wm_hor_alignment.value+"&gallery_wm_padding="+_.gallery_wm_padding.value+"&gallery_wm_hor_offset="+_.gallery_wm_hor_offset.value+"&gallery_wm_vrt_offset="+_.gallery_wm_vrt_offset.value+"&gallery_wm_x_transp="+_.gallery_wm_x_transp.value+"&gallery_wm_y_transp="+_.gallery_wm_y_transp.value+"&gallery_wm_font_color="+r.substring(1)+"&gallery_wm_use_drop_shadow="+t+"&gallery_wm_shadow_color="+g.substring(1)+"&gallery_wm_shadow_distance="+_.gallery_wm_shadow_distance.value+"&gallery_wm_opacity="+_.gallery_wm_opacity.value+"&gallery_wm_test_image_path="+_.gallery_wm_test_image_path.value;return window.open(w,"wm_tester","width=<?php echo $testwidth; ?>,height=<?php echo $testheight; ?>,screenX=0,screenY=0,top=0,left=0,toolbar=0,status=0,scrollbars=0,location=0,menubar=1,resizable=1"),!1};