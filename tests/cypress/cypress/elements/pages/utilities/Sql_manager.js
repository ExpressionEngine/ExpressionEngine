import ControlPanel from '../ControlPanel'

class SqlManager extends ControlPanel {
    constructor() {
        super()
        

        this.selectors = Object.assign(this.selectors, {
		  "table": 'table',
		  "tables": 'table tr td:first-child',
		  "manage_links": 'td li.view a',
		  "search_field": 'input[name=search]',
		  "search_btn": 'input[name=search_form]',
		  "op_select": 'select[name=table_action]',
		  "op_submit": '.tbl-bulk-act input[type=submit]'
        })

    }

     get_tables(){
    	return cy.task('db:query', 'SHOW TABLES').then(function([rows,fields]){

    		return rows;
    	})
    }

    load(){
    	this.open_dev_menu()
	    this.get('main_menu').find('a:contains("Utilities")').click()
	    this.get('wrap').find('a:contains("SQL Manager")').click()

    }

   


}
export default SqlManager