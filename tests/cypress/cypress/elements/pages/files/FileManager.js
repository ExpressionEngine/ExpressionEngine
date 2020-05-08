import FileManagerSection from '../_sections/FileManagerSection'

class FileManager extends FileManagerSection {
    constructor() {
        super()
				this.url = 'admin.php?/cp/files';
				this.urlMatch = /files/;

        this.elements({
					// Main box elements
					'heading': '.ee-main__content .title-bar .title-bar__title',
					'sync_button': 'a.icon--sync',

					'perpage_filter': 'div.filter-bar>.filter-bar__item:nth-child(3)',
					'perpage_filter_menu': 'div.filter-bar>.filter-bar__item:nth-child(3) .dropdown',
					'perpage_manual_filter': 'input[name="perpage"]',

					// Main box's table elements
					'files': '.ee-main__content form .table-responsive table tr',
					'selected_file': '.ee-main__content form .table-responsive table tr.selected',

					'title_name_header': '.ee-main__content form .table-responsive table tr th:first-child',
					'file_type_header': '.ee-main__content form .table-responsive table tr th:nth-child(2)',
					'date_added_header': '.ee-main__content form .table-responsive table tr th:nth-child(3)',
					'manage_header': '.ee-main__content form .table-responsive table tr th:nth-child(4)',
					'checkbox_header': '.ee-main__content form .table-responsive table tr th:nth-child(5)',

					'title_names': '.ee-main__content form .table-responsive table tr td:first-child',
					'file_types': '.ee-main__content form .table-responsive table tr td:nth-child(2)',
					'dates_added': '.ee-main__content form .table-responsive table tr td:nth-child(3)',
					'manage_actions': '.ee-main__content form .table-responsive table tr td:nth-child(4)',

					'bulk_action': 'form fieldset.bulk-action-bar select[name="bulk_action"]',
					'action_submit_button': 'form fieldset.bulk-action-bar button',

					'no_results': 'tr.no-results',

					'view_modal': 'div.modal-view-file',
					'view_modal_header': 'div.modal-view-file h1',

					'remove_directory_modal': 'div.modal-confirm-directory',
					'remove_directory_modal_submit_button': 'div.modal-confirm-directory .form-ctrls input.btn'
        })
		}
		load() {
			cy.contains('Files').click()
		}
}
export default FileManager;