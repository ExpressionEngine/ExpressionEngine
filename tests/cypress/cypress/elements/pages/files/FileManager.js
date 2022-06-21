import FileManagerSection from '../_sections/FileManagerSection'

class FileManager extends FileManagerSection {
    constructor() {
        super()
				this.url = 'admin.php?/cp/files';
				this.urlMatch = /files/;

        this.elements({
					// Main box elements
					'heading': '.ee-main .title-bar .title-bar__title',
					'sync_button': 'a.icon--sync',

					'perpage_filter': '.pagination__item .filter-search-bar__item',
					'perpage_filter_menu': '.pagination__item .filter-bar__button[data-filter-label="show"] + .dropdown',
					'perpage_manual_filter': 'input[type!=hidden][name="perpage"]',

					// Main box's table elements
					'files': '.ee-main__content form .table-responsive table tr',


					'title_name_header': '.ee-main__content form .table-responsive table tr th:nth-child(3)',
					'file_type_header': '.ee-main__content form .table-responsive table tr th:nth-child(5)',
					'date_added_header': '.ee-main__content form .table-responsive table tr th:nth-child(6)',
					'manage_header': '.ee-main__content form .table-responsive table tr th:nth-child(8)',
					'checkbox_header': '.ee-main__content form .table-responsive table tr th:nth-child(5) input[type=checkbox]',

					'title_names': '.ee-main__content form .table-responsive table tr td:nth-child(3)',
					'file_types': '.ee-main__content form .table-responsive table tr td:nth-child(5)',
					'dates_added': '.ee-main__content form .table-responsive table tr td:nth-child(6)',
					'manage_actions': '.ee-main__content form .table-responsive table tr td:nth-child(8)',

					'no_results': '.no-results',

					'view_modal': 'div.modal-view-file',
					'view_modal_header': 'div.modal-view-file h1',

					'remove_directory_modal': 'div.modal-confirm-directory',
					'remove_directory_modal_submit_button': 'div.modal-confirm-directory .form-ctrls .button'
        })
		}
		load() {
			cy.contains('Files').click()
		}
}
export default FileManager;
