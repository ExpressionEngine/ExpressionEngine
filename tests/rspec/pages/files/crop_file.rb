class CropFile < FileManagerPage
  set_url_matcher /files\/file\/crop/

  # Main box elements
  element :heading, 'div.form-standard form div.form-btns-top h1'
  element :crop_tab, 'ul.tabs a[rel="t-0"]'
  element :rotate_tab, 'ul.tabs a[rel="t-1"]'
  element :resize_tab, 'ul.tabs a[rel="t-2"]'
  element :save, 'div.form-standard form div.form-btns-top button'

  # Crop Form
  element :crop_width_input, 'form div.t-0 input[name="crop_width"]'
  element :crop_height_input, 'form div.t-0 input[name="crop_height"]'
  element :crop_x_input, 'form div.t-0 input[name="crop_x"]'
  element :crop_y_input, 'form div.t-0 input[name="crop_y"]'
  element :crop_image_preview, 'form div.t-0 figure.img-preview img'

  # Rotate Form
  element :rotate_right, 'form div.t-1 input[name="rotate"][value="270"]'
  element :rotate_left, 'form div.t-1 input[name="rotate"][value="90"]'
  element :flip_vertical, 'form div.t-1 input[name="rotate"][value="vrt"]'
  element :flip_horizontal, 'form div.t-1 input[name="rotate"][value="hor"]'
  element :rotate_image_preview, 'form div.t-1 figure.img-preview img'

  # Resize Form
  element :resize_width_input, 'form div.t-2 input[name="resize_width"]'
  element :resize_height_input, 'form div.t-2 input[name="resize_height"]'
  element :resize_image_preview, 'form div.t-2 figure.img-preview img'

  def load
    click_link 'Files'
    click_link 'About'
    file_name = find('div.box form table.app-listing tr:nth-child(2) td:first-child em').text
    find('div.box form table.app-listing tr:nth-child(2) td:nth-child(4) ul.toolbar li.crop').click

    return file_name
  end

end
