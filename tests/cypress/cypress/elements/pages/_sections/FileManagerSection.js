import ControlPanel from '../ControlPanel'

class FileManagerSection extends ControlPanel {
	constructor() {
		super()

		this.selectors = Object.assign(this.selectors, {
			// Title/header box elements
			'manager_title': '.main-nav__title',
			'title_toolbar': '.main-nav__toolbar .button--action',
			'download_all': 'a:contains("Export All Files")',
			// phrase_search, 'fieldset.tbl-search input[type!=hidden][name=search]',
			// search_submit_button, 'fieldset.tbl-search input.submit',
			'upload_new_file_button': '.main-nav__toolbar .js-dropdown-toggle',
			'upload_new_file_filter': '.main-nav__toolbar .dropdown',
			'upload_new_file_filter_menu': '.main-nav__toolbar .filter-submenu',
			'upload_new_file_filter_menu_items': '.main-nav__toolbar .dropdown a',

			// Sidebar elements
			'upload_directories_header': 'div.sidebar h2.sidebar__section-title',
			'new_directory_button': 'div.sidebar h2.sidebar__section-title .button--xsmall',
			'watermarks_header': 'div.sidebar .sidebar__link:contains("Watermarks")',
			'new_watermark_button': 'fieldset.right a.button.action',
			'folder_list': 'div.sidebar div.scroll-wrap .folder-list div',

			'selected_file': '.ee-main__content form .table-responsive table tr.selected',

		});
	}
}
export default FileManagerSection;
