import ControlPanel from '../ControlPanel'

class DebugExtensions extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/utilities/extensions';


        this.selectors = Object.assign(this.selectors, {
        	  "heading" : '.ee-main .title-bar .title-bar__title',
        	  "no_results" : 'form div.table-responsive table tr.no-results',
			  "addon_name_header" : 'form div.table-responsive table tr th:first-child',
			  "status_header" : 'form div.table-responsive table tr th:nth-child(2)',
			  "manage_header" : 'form div.table-responsive table tr th:nth-child(3)',
			  "checkbox_header" : 'form div.table-responsive table tr th:nth-child(4)',
			  "addons" : 'form div.table-responsive table tr',
			  "addon_names" : 'form div.table-responsive table tr td:first-child',
			  "statuses" : 'form div.table-responsive table tr td:nth-child(2)'
        })

    }

    load(){
		this.open_dev_menu()
    	cy.get('body > .dropdown').find('a:contains("Utilities")').click()
    	this.get('wrap').find('a:contains("DebugExtensions")').click()
    }

}
export default DebugExtensions;

