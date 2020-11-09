import ControlPanel from '../ControlPanel'

class SiteManager extends ControlPanel {
  constructor() {
    super()
    this.urlMatch = /msm/;
    this.elements({


      'settings_icon': '.section-header__options a.icon--settings',
      'title': '.section-header__title',
      'add_site_button': '.section-header__controls a.btn.action',

      'sites': '.tbl-wrap table tbody tr',
      'sites.id': 'td:first-child',
      'sites.name': 'td:nth-child(2)',
      'sites.short_name': 'td:nth-child(3)',
      'sites.status': 'td:nth-child(4)',
      'sites.manage': 'td:nth-child(5)',
      'sites.manage.edit': '.toolbar .edit a',
      'sites.bulk_action_checkbox': 'td:nth-child(6) input'

    })
  }

  load() {
    this.open_dev_menu()
    this.get('main_menu').find('a:contains("Site Manager")').click()
  }
}
export default SiteManager;