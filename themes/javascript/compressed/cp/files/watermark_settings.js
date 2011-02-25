/*
		function watermark_test()
		{
			var base 	= "<?php echo $basepath; ?>&P=wm_tester";
			var item 	= document.forms[0];
			var wm_font = (item.gallery_wm_use_font[0].checked) ? 'y' : 'n';
			var wm_drop = (item.gallery_wm_use_drop_shadow[0].checked) ? 'y' : 'n';
			var text_color = item.gallery_wm_text_color.value;
			var shad_color = item.gallery_wm_shadow_color.value;
					
			if (item.gallery_wm_type[1].checked)
			{
				var wm_type = 't';			
			}
			else if (item.gallery_wm_type[2].checked)
			{
				var wm_type = 'g';
			}
			
			var theText = item.gallery_wm_text.value;
			
			theText = theText.replace('/;/g', '').replace('?', '');
											
			var loc = base + 
			'&gallery_wm_type=' + wm_type +
			'&gallery_wm_text=' + theText +	
			'&gallery_wm_image_path=' + item.gallery_wm_image_path.value +	
			'&gallery_wm_use_font=' + wm_font +
			'&gallery_wm_font=' + item.gallery_wm_font.value +	
			'&gallery_wm_font_size=' + item.gallery_wm_font_size.value +					
			'&gallery_wm_vrt_alignment=' + item.gallery_wm_vrt_alignment.value +					
			'&gallery_wm_hor_alignment=' + item.gallery_wm_hor_alignment.value +					
			'&gallery_wm_padding=' + item.gallery_wm_padding.value +					
			'&gallery_wm_x_offset=' + item.gallery_wm_x_offset.value +					
			'&gallery_wm_y_offset=' + item.gallery_wm_y_offset.value +					
			'&gallery_wm_x_transp=' + item.gallery_wm_x_transp.value +	
			'&gallery_wm_y_transp=' + item.gallery_wm_y_transp.value +	
			'&gallery_wm_text_color=' + text_color.substring(1) +					
			'&gallery_wm_use_drop_shadow=' + wm_drop +					
			'&gallery_wm_shadow_color=' + shad_color.substring(1) +					
			'&gallery_wm_shadow_distance=' + item.gallery_wm_shadow_distance.value +					
			'&gallery_wm_opacity=' + item.gallery_wm_opacity.value +					
			'&gallery_wm_test_image_path=' + item.gallery_wm_test_image_path.value;		
									
			window.open(loc, 'wm_tester','width=<?php echo $testwidth; ?>,height=<?php echo $testheight; ?>,screenX=0,screenY=0,top=0,left=0,toolbar=0,status=0,scrollbars=0,location=0,menubar=1,resizable=1');

			return false;
		}
*/

$("#wm_text_color, #wm_shadow_color").ColorPicker({onSubmit:function(c,b,d,a){$(a).val(b);$(a).ColorPickerHide()},onBeforeShow:function(){$(this).ColorPickerSetColor(this.value)}}).bind("keyup",function(){$(this).ColorPickerSetColor(this.value)});var type=$('input[name="wm_type"]:checked').val();type=="text"?$(".image_type").hide():$(".text_type").hide();$("input[name=wm_type]").change(function(){$(".text_type").toggle();$(".image_type").toggle()});
