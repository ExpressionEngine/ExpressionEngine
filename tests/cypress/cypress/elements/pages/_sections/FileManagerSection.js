import ControlPanel from '../ControlPanel'

class FileManagerSection extends ControlPanel {
	constructor() {
		super()

		this.selectors = Object.assign(this.selectors, {
			// Title/header box elements
			'manager_title': '.section-header__title',
			'title_toolbar': '.section-header__options',
			'download_all': 'a.icon--export',
			// phrase_search, 'fieldset.tbl-search input[name=search]',
			// search_submit_button, 'fieldset.tbl-search input.submit',
			'upload_new_file_button': '.section-header__controls a.filter-item__link--action',
			'upload_new_file_filter': '.section-header__controls .filter-item',
			'upload_new_file_filter_menu': '.section-header__controls .filter-submenu',
			'upload_new_file_filter_menu_items': '.section-header__controls .filter-submenu a',
		
			// Sidebar elements
			'upload_directories_header': 'div.sidebar h2:first-child',
			'new_directory_button': 'div.sidebar h2:first-child a.btn.action',
			'watermarks_header': 'div.sidebar h2:nth-child(3)',
			'new_watermark_button': 'div.sidebar h2:nth-child(3) a.btn.action',
			'folder_list': 'div.sidebar div.scroll-wrap ul.folder-list li'
		
		});
	}
}
export default FileManagerSection;