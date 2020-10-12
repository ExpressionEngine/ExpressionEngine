import ControlPanel from '../ControlPanel'

class MemberManagerSection extends ControlPanel {
	constructor() {
		super()

		this.selectors = Object.assign(this.selectors, {
			// Title/header box elements
			'heading': '.ee-main .title-bar .title-bar__title',
			'keyword_search': 'input[type!=hidden][name=filter_by_keyword]',

			'member_actions': 'select[name=bulk_action]',
			'member_table': 'table',

			'members': 'table tbody tr',
			'selected_member': 'table tbody tr.selected',

			'id_header': '.ee-main__content form .table-responsive table tr th:first-child',
			'username_header': '.ee-main__content form .table-responsive table tr th:nth-child(2)',
			'dates_header': '.ee-main__content form .table-responsive table tr th:nth-child(3)',
			'member_group_header': '.ee-main__content form .table-responsive table tr th:nth-child(4)',
			'manage_header': '.ee-main__content form .table-responsive table tr th:nth-child(5)',
			'checkbox_header': '.ee-main__content form .table-responsive table tr th:nth-child(6)',

			'ids': '.ee-main__content form .table-responsive table tr td:first-child',
			'usernames': '.ee-main__content form .table-responsive table tr td:nth-child(2) div > a',
			'emails': '.ee-main__content form .table-responsive table tr td:nth-child(2) span.meta-info a',
			'dates': '.ee-main__content form .table-responsive table tr td:nth-child(3)',
			'member_groups': '.ee-main__content form .table-responsive table tr td:nth-child(4)',
			'manage_actions': '.ee-main__content form .table-responsive table tr td:nth-child(5)',

			'no_results': 'tr.no-results'

		});
	}
}
export default MemberManagerSection;