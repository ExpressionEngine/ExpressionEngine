import ControlPanel from '../ControlPanel'

class SiteManager extends ControlPanel {
  constructor() {
    super()
    this.urlMatch = /msm/;
    this.elements({


      'settings_icon': '.section-header__options a.icon--settings',
      'title': '.section-header__title',
      'add_site_button': '.main-nav__toolbar a.button--action',

      'sites': '.table-responsive table tbody tr',
      'sites.id': 'td:first-child',
      'sites.name': 'td:nth-child(2)',
      'sites.short_name': 'td:nth-child(3)',
      'sites.status': 'td:nth-child(4)',
      'sites.bulk_action_checkbox': 'td:nth-child(5) input'

    })
  }

  load() {
    this.open_dev_menu()
    cy.get('body > .dropdown').find('a:contains("Sites")').click()
  }
}
export default SiteManager;