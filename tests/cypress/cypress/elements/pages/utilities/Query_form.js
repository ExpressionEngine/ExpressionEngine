import ControlPanel from '../ControlPanel'

class QueryForm extends ControlPanel {
    constructor() {
        super()
        //this.url = no url passed;

        this.selectors = Object.assign(this.selectors, {
        	 "query_form" : 'textarea[name=thequery]'
        })

    }

    load(){
		this.open_dev_menu()
	    this.get('main_menu').find('a:contains("Utilities")').click()
	    this.get('wrap').find('a:contains("Query Form")').click()
    }

}

export default QueryForm;
