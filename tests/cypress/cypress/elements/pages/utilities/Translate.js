import ControlPanel from '../ControlPanel'

class Translate extends ControlPanel {
  constructor() {
    super()
    this.urlMatcher = /utilities\/translate/

    this.elements({
      'languages': 'div.sidebar ul:nth-child(4) li',

      'heading': 'div.w-12 form h1',

      'phrase_search': 'form fieldset.tbl-search input[type!=hidden][name=search]',
      'search_submit_button': 'form fieldset.tbl-search input.submit',

      'table': 'form table',
      'no_results': 'form table tr.no-results',
      'rows': 'form table tr'
    })
  }

  load() {
    this.open_dev_menu()
    cy.get('body > .dropdown').find('a:contains("Utilities")').click()
    this.get('wrap').find('a:contains("English")').click()
  }

}
export default Translate;