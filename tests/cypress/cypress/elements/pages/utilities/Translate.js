import ControlPanel from '../ControlPanel'

class Translate extends ControlPanel {
  constructor() {
    super()
    this.urlMatcher = /utilities\/translate/

    this.elements({
      'languages': 'div.sidebar ul:nth-child(4) li',

      'heading': 'div.w-12 form h1',

      'phrase_search': 'form fieldset.tbl-search input[name=search]',
      'search_submit_button': 'form fieldset.tbl-search input.submit',

      'table': 'form table',
      'no_results': 'form table tr.no-results',
      'rows': 'form table tr',

      'bulk_action': 'form fieldset.tbl-bulk-act select[name="bulk_action"]',
      'action_submit_button': 'form fieldset.tbl-bulk-act input.submit'
    })
  }

  load() {
    this.open_dev_menu()
    this.get('main_menu').find('a:contains("Utilities")').click()
    this.get('wrap').find('a:contains("English")').click()
  }

}
export default Translate;