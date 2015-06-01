class CropFile < FileManagerPage
	set_url_matcher /files\/file\/crop/

	# Main box elements
	element :heading, 'div.col.w-16 div.box h1'
	element :crop_tab, 'ul.tabs a[rel="t-0"]'
	element :rotate_tab, 'ul.tabs a[rel="t-1"]'
	element :resize_tab, 'ul.tabs a[rel="t-2"]'

	# Crop Form
	element :crop_width_input, 'form div.t-0 input[name="crop_width"]'
	element :crop_height_input, 'form div.t-0 input[name="crop_height"]'
	element :crop_x_input, 'form div.t-0 input[name="crop_x"]'
	element :crop_y_input, 'form div.t-0 input[name="crop_y"]'
	element :crop_image_preview, 'form div.t-0 figure.img-preview img'
	element :crop_submit_button, 'form div.t-0 fieldset.form-ctrls input[type="submit"]'

	# Rotate Form
	element :rotate_right, 'form div.t-1 input[name="rotate"][value="270"]', visible: false
	element :rotate_left, 'form div.t-1 input[name="rotate"][value="90"]', visible: false
	element :flip_vertical, 'form div.t-1 input[name="rotate"][value="vrt"]', visible: false
	element :flip_horizontal, 'form div.t-1 input[name="rotate"][value="hor"]', visible: false
	element :rotate_image_preview, 'form div.t-1 figure.img-preview img', visible: false
	element :rotate_submit_button, 'form div.t-1 fieldset.form-ctrls input[type="submit"]', visible: false

	# Resize Form
	element :resize_width_input, 'form div.t-2 input[name="resize_width"]', visible: false
	element :resize_height_input, 'form div.t-2 input[name="resize_height"]', visible: false
	element :resize_image_preview, 'form div.t-2 figure.img-preview img', visible: false
	element :resize_submit_button, 'form div.t-2 fieldset.form-ctrls input[type="submit"]', visible: false

	def load
		click_link 'Files'
		click_link 'About'
		file_name = find('div.box form div.tbl-wrap table tr:nth-child(2) td:first-child em').text
		find('div.box form div.tbl-wrap table tr:nth-child(2) td:nth-child(4) ul.toolbar li.crop').click

		return file_name
	end

end