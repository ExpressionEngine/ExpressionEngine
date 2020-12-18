import ControlPanel from '../ControlPanel'

class SqlManager extends ControlPanel {
    constructor() {
        super()


        this.selectors = Object.assign(this.selectors, {
		  "table": 'table',
		  "tables": 'table tr td:first-child',
		  "manage_links": 'td li.view a',
		  "search_field": 'input[type!=hidden][name=search]',
		  "search_btn": 'input[type!=hidden][name=search_form]',
		  "op_select": 'select[name=bulk_action]',
		  "op_submit": '.bulk-action-bar [type=submit]'
        })

    }

     get_tables(){
    	return cy.task('db:query', 'SHOW TABLES').then(function([rows,fields]){

    		return rows;
    	})
    }

    load(){
    	this.open_dev_menu()
	    cy.get('body > .dropdown').find('a:contains("Utilities")').click()
	    this.get('wrap').find('a:contains("SQL Manager")').click()

    }




}
export default SqlManager