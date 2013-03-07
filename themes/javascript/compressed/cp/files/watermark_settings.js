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

$(document).ready(function(){$.ee_watermark()});$.ee_watermark=function(){"text"==$('input[name="wm_type"]:checked').val()?$(".image_type").hide():$(".text_type").hide();$.ee_watermark.type_toggle()};$.ee_watermark.type_toggle=function(){$("input[name=wm_type]").change(function(){$(".text_type").toggle();$(".image_type").toggle()})};
$.ee_watermark.watermark_test=function(){var a=document.forms[0],b="",d=a.gallery_wm_use_font[0].checked?"y":"n",e=a.gallery_wm_use_drop_shadow[0].checked?"y":"n",f=a.gallery_wm_font_color.value,g=a.gallery_wm_shadow_color.value;a.gallery_wm_type[1].checked?b="t":a.gallery_wm_type[2].checked&&(b="g");var c=a.gallery_wm_text.value,c=c.replace("/;/g","").replace("?",""),a="<?php echo $basepath; ?>&P=wm_tester&gallery_wm_type="+b+"&gallery_wm_text="+c+"&gallery_wm_image_path="+a.gallery_wm_image_path.value+
"&gallery_wm_use_font="+d+"&gallery_wm_font="+a.gallery_wm_font.value+"&gallery_wm_font_size="+a.gallery_wm_font_size.value+"&gallery_wm_vrt_alignment="+a.gallery_wm_vrt_alignment.value+"&gallery_wm_hor_alignment="+a.gallery_wm_hor_alignment.value+"&gallery_wm_padding="+a.gallery_wm_padding.value+"&gallery_wm_hor_offset="+a.gallery_wm_hor_offset.value+"&gallery_wm_vrt_offset="+a.gallery_wm_vrt_offset.value+"&gallery_wm_x_transp="+a.gallery_wm_x_transp.value+"&gallery_wm_y_transp="+a.gallery_wm_y_transp.value+
"&gallery_wm_font_color="+f.substring(1)+"&gallery_wm_use_drop_shadow="+e+"&gallery_wm_shadow_color="+g.substring(1)+"&gallery_wm_shadow_distance="+a.gallery_wm_shadow_distance.value+"&gallery_wm_opacity="+a.gallery_wm_opacity.value+"&gallery_wm_test_image_path="+a.gallery_wm_test_image_path.value;window.open(a,"wm_tester","width=<?php echo $testwidth; ?>,height=<?php echo $testheight; ?>,screenX=0,screenY=0,top=0,left=0,toolbar=0,status=0,scrollbars=0,location=0,menubar=1,resizable=1");return!1};
