import ControlPanel from '../ControlPanel'

class MemberManagerSection extends ControlPanel {
	constructor() {
		super()

		this.selectors = Object.assign(this.selectors, {
			// Title/header box elements
			'heading': 'div.col.w-12 div.box form h1',
			'keyword_search': 'input[name=filter_by_keyword]',

			'member_actions': 'select[name=bulk_action]',
			'member_table': 'table',

			'members': 'table tbody tr',
			'selected_member': 'table tbody tr.selected',

			'id_header': 'div.box form div.tbl-wrap table tr th:first-child',
			'username_header': 'div.box form div.tbl-wrap table tr th:nth-child(2)',
			'dates_header': 'div.box form div.tbl-wrap table tr th:nth-child(3)',
			'member_group_header': 'div.box form div.tbl-wrap table tr th:nth-child(4)',
			'manage_header': 'div.box form div.tbl-wrap table tr th:nth-child(5)',
			'checkbox_header': 'div.box form div.tbl-wrap table tr th:nth-child(6)',

			'ids': 'div.box form div.tbl-wrap table tr td:first-child',
			'usernames': 'div.box form div.tbl-wrap table tr td:nth-child(2) > a',
			'emails': 'div.box form div.tbl-wrap table tr td:nth-child(2) span.meta-info a',
			'dates': 'div.box form div.tbl-wrap table tr td:nth-child(3)',
			'member_groups': 'div.box form div.tbl-wrap table tr td:nth-child(4)',
			'manage_actions': 'div.box form div.tbl-wrap table tr td:nth-child(5)',

			'bulk_action': 'form fieldset.tbl-bulk-act select[name="bulk_action"]',
			'action_submit_button': 'form fieldset.tbl-bulk-act button.submit',

			'no_results': 'tr.no-results'

		});
	}
}
export default MemberManagerSection;