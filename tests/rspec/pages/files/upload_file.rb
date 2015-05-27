class UploadFile < ControlPanelPage
	set_url_matcher /files\/upload/

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
	element :heading, 'div.col.w-12 div.box h1'

	# Edit form
	element :file_input, 'div.col.w-12 div.box form fieldset input[name="file"]'
	element :title_input, 'div.col.w-12 div.box form fieldset input[name="title"]'
	element :description_input, 'div.col.w-12 div.box form fieldset textarea[name="description"]'
	element :credit_input, 'div.col.w-12 div.box form fieldset input[name="credit"]'
	element :location_input, 'div.col.w-12 div.box form fieldset input[name="location"]'
	element :form_submit_button, 'div.col.w-12 div.box form fieldset.form-ctrls input[type="submit"]'

	def load
		click_link 'Files'
		click_link 'Main Upload Directory'
		click_link 'Upload New File'
	end

end