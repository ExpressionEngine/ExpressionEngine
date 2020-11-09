import ControlPanel from '../ControlPanel'

class TranslateEdit extends ControlPanel {
  constructor() {
    super()
    this.urlMatcher = /utilities\/translate\/\w+\/edit/

    this.elements({

      'heading': 'div.w-12 form h1',

      'items': 'form fieldset',

      'submit_button': 'form div.form-btns.form-btns-top input[type="submit"]'
    })
  }

  load() {
    this.open_dev_menu()
    this.get('main_menu').find('a:contains("Utilities")').click()
    this.get('wrap').find('a:contains("English")').click()
    this.get('wrap').find('ul.toolbar li.edit a').first().click() // The addons_lang.php edit link
  }

}
export default TranslateEdit;