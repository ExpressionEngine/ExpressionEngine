class FileManagerPage < ControlPanelPage

	# Title/header box elements
	element :manager_title, 'div.box.full.mb form h1'
	element :title_toolbar, 'div.box.full.mb form h1 ul.toolbar'
	element :download_all, 'div.box.full.mb form h1 ul.toolbar li.download'
	element :phrase_search, 'fieldset.tbl-search input[name=search]'
	element :search_submit_button, 'fieldset.tbl-search input.submit'

	# Sidebar elements
	element :upload_directories_header, 'div.sidebar h2:first-child'
	element :new_directory_button, 'div.sidebar h2:first-child a.btn.action'
	element :watermarks_header, 'div.sidebar h2:nth-child(3)'
	element :new_watermark_button, 'div.sidebar h2:nth-child(3) a.btn.action'
	elements :folder_list, 'div.sidebar div.scroll-wrap ul.folder-list li'

end