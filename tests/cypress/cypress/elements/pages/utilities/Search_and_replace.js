import ControlPanel from '../ControlPanel'

class SearchAndReplace extends ControlPanel {
    constructor() {
        super()
        //this.url = none?;

        this.selectors = Object.assign(this.selectors, {
			  "search_term" : 'textarea[name=search_term]',
			  "replace_term" : 'textarea[name=replace_term]',
			  "replace_where" : 'select[name=replace_where]',
			  "password_auth" : 'input[type!=hidden][name=password_auth]'
        })
    }


  load() {
    this.open_dev_menu()
    cy.get('body > .dropdown').find('a:contains("Utilities")').click()
    this.get('wrap').find('a:contains("Search and Replace")').click()
  }


}
export default SearchAndReplace;