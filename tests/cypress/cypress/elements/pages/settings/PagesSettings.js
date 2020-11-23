import ControlPanel from '../ControlPanel'

class PagesSettings extends ControlPanel {
  constructor() {
      super()
      this.url = 'admin.php?/cp/addons/settings/pages/settings'

      this.elements({
        'homepage_display': 'input[name="homepage_display"]',
        'default_channel': 'input[name="default_channel"]',
        'channel_default_template': '.field-inputs select'

      })
  }
}
export default PagesSettings;