class WatermarkEdit < FileManagerPage

  element :wm_name, 'input[name=wm_name]'
  elements :wm_type, 'input[name=wm_type]'
  elements :wm_vrt_alignment, 'input[name=wm_vrt_alignment]'
  elements :wm_hor_alignment, 'input[name=wm_hor_alignment]'
  element :wm_padding, 'input[name=wm_padding]'
  element :wm_hor_offset, 'input[name=wm_hor_offset]'
  element :wm_vrt_offset, 'input[name=wm_vrt_offset]'

  # Text options
  element :wm_use_font, 'a.toggle-btn[data-toggle-for="wm_use_font"]'
  element :wm_text, 'input[name=wm_text]'
  elements :wm_font, 'input[name=wm_font]'
  element :wm_font_size, 'input[name=wm_font_size]'
  element :wm_font_color, 'input[name=wm_font_color]'
  element :wm_use_drop_shadow, 'a.toggle-btn[data-toggle-for="wm_use_drop_shadow"]'
  element :wm_shadow_distance, 'input[name=wm_shadow_distance]'
  element :wm_shadow_color, 'input[name=wm_shadow_color]'

  # Image options
  element :wm_image_path, 'input[name=wm_image_path]'
  element :wm_opacity, 'input[name=wm_opacity]'
  element :wm_x_transp, 'input[name=wm_x_transp]'
  element :wm_y_transp, 'input[name=wm_y_transp]'

  def load
    click_link 'Files'
    within 'div.sidebar h2:nth-of-type(2)' do
      click_link 'New'
    end
  end

  def load_edit_for_watermark(number)
    click_link 'Watermarks'

    find('tbody tr:nth-child('+number.to_s+') li.edit a').click
  end
end
