import ControlPanel from '../ControlPanel'

class Stats extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/utilities/stats';

        this.selectors = Object.assign(this.selectors, {
        	  "heading": 'div.box form h1',
			  "content_table": 'div.box form table',
			  "rows": 'div.box form table tr',
			  "sources": 'div.box form table tr td:first-child',
			  "counts": 'div.box form table tr td:nth-child(2)',
			  "bulk_action": 'form fieldset.tbl-bulk-act select[name="bulk_action"]',
			  "action_submit_button": 'form fieldset.tbl-bulk-act input.submit'
        })
    }

    load(){
	    this.open_dev_menu()
	    this.get('main_menu').find('a:contains("Utilities")').click()
	    this.get('wrap').find('a:contains("Statistics")').click()
    }


}
export default Stats;