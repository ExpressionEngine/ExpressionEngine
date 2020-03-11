import FileManagerSection from '../_sections/FileManagerSection'

class FileManager extends FileManagerSection {
    constructor() {
        super()
				this.url = 'admin.php?/cp/files';
				this.urlMatch = /files/;

        this.elements({
					// Main box elements
					'heading': 'div.col.w-12 div.box form h1',
					'sync_button': 'a.icon--sync',
				
					'perpage_filter': 'div.filters>ul>li:nth-child(2)',
					'perpage_filter_menu': 'div.filters>ul>li:nth-child(2) div.sub-menu ul',
					'perpage_manual_filter': 'input[name="perpage"]',
				
					// Main box's table elements
					'files': 'div.box form div.tbl-wrap table tr',
					'selected_file': 'div.box form div.tbl-wrap table tr.selected',
				
					'title_name_header': 'div.box form div.tbl-wrap table tr th:first-child',
					'file_type_header': 'div.box form div.tbl-wrap table tr th:nth-child(2)',
					'date_added_header': 'div.box form div.tbl-wrap table tr th:nth-child(3)',
					'manage_header': 'div.box form div.tbl-wrap table tr th:nth-child(4)',
					'checkbox_header': 'div.box form div.tbl-wrap table tr th:nth-child(5)',
				
					'title_names': 'div.box form div.tbl-wrap table tr td:first-child',
					'file_types': 'div.box form div.tbl-wrap table tr td:nth-child(2)',
					'dates_added': 'div.box form div.tbl-wrap table tr td:nth-child(3)',
					'manage_actions': 'div.box form div.tbl-wrap table tr td:nth-child(4)',
				
					'bulk_action': 'form fieldset.tbl-bulk-act select[name="bulk_action"]',
					'action_submit_button': 'form fieldset.tbl-bulk-act button.submit',
				
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