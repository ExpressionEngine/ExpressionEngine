import ControlPanel from '../ControlPanel'

class SearchAndReplace extends ControlPanel {
    constructor() {
        super()
        //this.url = none?;

        this.selectors = Object.assign(this.selectors, {
			  "search_term" : 'textarea[name=search_term]',
			  "replace_term" : 'textarea[name=replace_term]',
			  "replace_where" : 'select[name=replace_where]',
			  "password_auth" : 'input[name=password_auth]'
        })
    }


  load() {
    // this.open_dev_menu()
    // this.get('main_menu').find('a:contains("Utilities")').click()
    // this.get('wrap').find('ul').eq(4).find('li').eq(2).click()
    cy.visit('http://localhost:8888/admin.php?/cp/utilities/sandr')
  }


}
export default SearchAndReplace;