import ControlPanel from '../ControlPanel'

class SiteForm extends ControlPanel {
  constructor() {
    super()
    this.urlMatch = /msm\/edit/;
    this.elements({

      'settings_icon': '.section-header__options a.icon--settings',
      'title': '.section-header__title',
      'add_site_button': '.section-header__controls a.btn.action',

      'save_button': 'div.form-btns.form-btns-top button[value="save_and_close"]',
      'name': 'input[name="site_label"]',
      'short_name': 'input[name="site_name"]',
      'online': 'a[data-toggle-for="is_site_on"]',
      'description': 'textarea[name="site_description"]'

    })
  }

  add_site(passed_options = {}) {
    const defaults = {
      name: 'Rspec Site',
      short_name: 'rspec_site',
      description: 'Lorem ipsum...'
    }

    const options = Cypress._.merge(defaults, passed_options)

    this.get('name').type(options.name)
    this.get('short_name').clear().type(options.short_name)
    this.get('description').type(options.description)
    this.get('save_button').click()
  }

  load_edit_for_site(id) {
    this.open_dev_menu()
    this.get('main_menu').find('a:contains("Site Manager")').click()
    this.get('wrap').find('a[href*="cp/msm/edit/' + id + '"]').click()
  }
}
export default SiteForm;