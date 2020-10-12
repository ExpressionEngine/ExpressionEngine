import ControlPanel from '../ControlPanel'

class SpamTrap extends ControlPanel {
    constructor() {
		super()

		this.urlMatch = /addons\/settings\/spam/

		this.elements({

			'keyword_search': 'div.filter-bar input[type!=hidden][name=filter_by_keyword]',

			// Main box elements
			'heading': '.align-right div.col.w-16 div.box form h1',
			'perpage_filter': 'div.col.w-16 div.box form h1 + div.filters ul li:first-child',
			'perpage_filter_menu': 'div.col.w-16 div.box form h1 + div.filters ul li:first-child div.sub-menu ul',
			'perpage_manual_filter': 'input[type!=hidden][name="perpage"]',

			// Main box's table elements
			'spam_trap': '.ee-main__content form .table-responsive table tr',
			'selected_spam': '.ee-main__content form .table-responsive table tr.selected',

			'content_header': '.ee-main__content form .table-responsive table tr th:first-child',
			'date_header': '.ee-main__content form .table-responsive table tr th:nth-child(2)',
			'ip_header': '.ee-main__content form .table-responsive table tr th:nth-child(3)',
			'type_header': '.ee-main__content form .table-responsive table tr th:nth-child(4)',
			'manage_header': '.ee-main__content form .table-responsive table tr th:nth-child(4)',
			'checkbox_header': '.ee-main__content form .table-responsive table tr th:nth-child(5)',
			'check_all': '.ee-main__content form .table-responsive table tr th:nth-child(5) input',

			'content': '.ee-main__content form .table-responsive table tr td:first-child',
			'date': '.ee-main__content form .table-responsive table tr td:nth-child(2)',
			'ip_addresses': '.ee-main__content form .table-responsive table tr td:nth-child(3)',
			'manage_actions': '.ee-main__content form .table-responsive table tr td:nth-child(4)',

			'no_results': 'tr.no-results',

			'view_modal': 'div.spam-modal',
			'view_modal_header': 'div.spam-modal h1'
		})

	}

	load() {
		this.get('main_menu').find('a:contains("Add-Ons")').click()
		this.get('wrap').find('.add-on-card:contains("Spam")').first().click()
	}
}
export default SpamTrap;
