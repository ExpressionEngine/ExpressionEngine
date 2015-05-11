class CropFile < ControlPanelPage
	set_url_matcher /files\/file\/crop/

  # Title/header box elements
	element :title, 'div.box.full.mb form h1'
	element :title_toolbar, 'div.box.full.mb form h1 ul.toolbar'
	element :download_all, 'div.box.full.mb form h1 ul.toolbar li.download'
	element :phrase_search, 'fieldset.tbl-search input[name=search]'
	element :search_submit_button, 'fieldset.tbl-search input.submit'

	# Sidebar elements
	element :sidebar, 'div.sidebar'
	element :upload_directories_header, 'div.sidebar h2:first-child'
	element :new_directory_button, 'div.sidebar h2:first-child a.btn.action'
	element :watermarks_header, 'div.sidebar h2:nth-child(3)'
	element :new_watermark_button, 'div.sidebar h2:nth-child(3) a.btn.action'
	elements :folder_list, 'div.sidebar div.scroll-wrap ul.folder-list li'

	# Main box elements
	element :breadcrumb, 'ul.breadcrumb'
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
	element :rotate_right, 'form div.t-1 input[name="rotate"][value="270"]'
	element :rotate_left, 'form div.t-1 input[name="rotate"][value="90"]'
	element :flip_vertical, 'form div.t-1 input[name="rotate"][value="vrt"]'
	element :flip_horizontal, 'form div.t-1 input[name="rotate"][value="hor"]'
	element :rotate_image_preview, 'form div.t-1 figure.img-preview img'
	element :rotate_submit_button, 'form div.t-1 fieldset.form-ctrls input[type="submit"]'

	# Resize Form
	element :resize_width_input, 'form div.t-2 input[name="resize_width"]'
	element :resize_height_input, 'form div.t-2 input[name="resize_height"]'
	element :resize_image_preview, 'form div.t-2 figure.img-preview img'
	element :resize_submit_button, 'form div.t-2 fieldset.form-ctrls input[type="submit"]'

	def load
		click_link 'Files'
		file_name = find('div.box form div.tbl-wrap table tr:nth-child(2) td:first-child em').text
		find('div.box form div.tbl-wrap table tr:nth-child(2) td:nth-child(4) ul.toolbar li.crop').click

		return file_name
	end

end