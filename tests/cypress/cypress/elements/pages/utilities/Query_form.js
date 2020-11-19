import ControlPanel from '../ControlPanel'

class QueryForm extends ControlPanel {
    constructor() {
        super()
        //this.url = no url passed;

        this.selectors = Object.assign(this.selectors, {
        	 "query_form" : '.CodeMirror-line'
        })

    }

    load(){
		this.open_dev_menu()
	    cy.get('body > .dropdown').find('a:contains("Utilities")').click()
	    this.get('wrap').find('a:contains("Query Form")').click()
    }

}

export default QueryForm;
