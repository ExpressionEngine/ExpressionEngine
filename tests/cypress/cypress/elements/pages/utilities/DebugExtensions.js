import ControlPanel from '../ControlPanel'

class DebugExtensions extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/utilities/extensions';


        this.selectors = Object.assign(this.selectors, {
        	  "heading" : 'div.w-12 div.box h1',
        	  "no_results" : 'div.box div.tbl-ctrls form div.tbl-wrap table tr.no-results',
			  "addon_name_header" : 'div.box div.tbl-ctrls form div.tbl-wrap table tr th:first-child',
			  "status_header" : 'div.box div.tbl-ctrls form div.tbl-wrap table tr th:nth-child(2)',
			  "manage_header" : 'div.box div.tbl-ctrls form div.tbl-wrap table tr th:nth-child(3)',
			  "checkbox_header" : 'div.box div.tbl-ctrls form div.tbl-wrap table tr th:nth-child(4)',
			  "addons" : 'div.box div.tbl-ctrls form div.tbl-wrap table tr',
			  "addon_names" : 'div.box div.tbl-ctrls form div.tbl-wrap table tr td:first-child',
			  "statuses" : 'div.box div.tbl-ctrls form div.tbl-wrap table tr td:nth-child(2)',
			  "bulk_action" : 'form fieldset.tbl-bulk-act select[name="bulk_action"]',
			  "action_submit_button" : 'form fieldset.tbl-bulk-act input.submit'
        })

    }

    load(){
		this.open_dev_menu()
    	this.get('main_menu').find('a:contains("Utilities")').click()
    	this.get('wrap').find('a:contains("DebugExtensions")').click()
    }

}
export default DebugExtensions;

