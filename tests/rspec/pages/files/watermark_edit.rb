class WatermarkEdit < FileManagerPage

	element :wm_name, 'input[name=wm_name]'
	element :wm_type, 'select[name=wm_type]'
	element :wm_vrt_alignment, 'input[name=wm_vrt_alignment]'
	element :wm_hor_alignment, 'input[name=wm_hor_alignment]'
	element :wm_padding, 'input[name=wm_padding]'
	element :wm_hor_offset, 'input[name=wm_hor_offset]'
	element :wm_vrt_offset, 'input[name=wm_vrt_offset]'

	# Text options
	elements :wm_use_font, 'input[name=wm_use_font]'
	element :wm_text, 'input[name=wm_text]'
	element :wm_font, 'select[name=wm_font]'
	element :wm_font_size, 'input[name=wm_font_size]'
	element :wm_font_color, 'input[name=wm_font_color]'
	elements :wm_use_drop_shadow, 'input[name=wm_use_drop_shadow]'
	element :wm_shadow_distance, 'input[name=wm_shadow_distance]'
	element :wm_shadow_color, 'input[name=wm_shadow_color]'

	# Image options
	element :wm_image_path, 'input[name=wm_image_path]', visible: false
	element :wm_opacity, 'input[name=wm_opacity]', visible: false
	element :wm_x_transp, 'input[name=wm_x_transp]', visible: false
	element :wm_y_transp, 'input[name=wm_y_transp]', visible: false

	def load
		click_link 'Files'
		within 'div.sidebar h2:nth-child(2)' do
			click_link 'New'
		end
	end

	def load_edit_for_watermark(number)
		click_link 'Watermarks'

		find('tbody tr:nth-child('+number.to_s+') li.edit a').click
	end
end