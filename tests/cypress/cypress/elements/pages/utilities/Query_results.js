import ControlPanel from '../ControlPanel'

class QueryResults extends ControlPanel {
    constructor() {
        super()
        //this.url = ;

        this.selectors = Object.assign(this.selectors, {
        	  "search_field" : 'input[type!=hidden][name=search]',
			  "search_btn": 'input[type=submit]',
			  "table" :'table',
			  "rows": 'div.box form table tbody tr',
			  "first_column" :'table tr td:first-child'

        })

    }

}
export default QueryResults;