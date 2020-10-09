import ControlPanel from '../ControlPanel'

class ThrottlingSettings extends ControlPanel {
  constructor() {
      super()

      this.elements({
        'enable_throttling': 'input[name=enable_throttling]', //visible => false
        'enable_throttling_toggle': '[data-toggle-for=enable_throttling]',
        'banish_masked_ips': 'input[name=banish_masked_ips]', //visible => false
        'banish_masked_ips_toggle': '[data-toggle-for=banish_masked_ips]',
        'lockout_time': 'input[type!=hidden][name=lockout_time]',
        'max_page_loads': 'input[type!=hidden][name=max_page_loads]',
        'time_interval': 'input[type!=hidden][name=time_interval]',
        'banishment_type': 'input[type!=hidden][name=banishment_type]',
        'banishment_url': 'input[type!=hidden][name=banishment_url]',
        'banishment_message': 'textarea[name=banishment_message]'
      })
  }

  load() {
    this.get('settings_btn').click()
    this.get('wrap').find('div.sidebar a:contains("Access Throttling")').click()
  }
}
export default ThrottlingSettings;