import ControlPanel from '../ControlPanel'

class HitTracking extends ControlPanel {
  constructor() {
      super()

      this.elements({
        'enable_online_user_tracking_toggle': '[data-toggle-for=enable_online_user_tracking]',
        'enable_online_user_tracking': 'input[name=enable_online_user_tracking]',//: :visible => false
        'enable_hit_tracking_toggle': '[data-toggle-for=enable_hit_tracking]',
        'enable_hit_tracking': 'input[name=enable_hit_tracking]',//: :visible => false
        'enable_entry_view_tracking_toggle': '[data-toggle-for=enable_entry_view_tracking]',
        'enable_entry_view_tracking': 'input[name=enable_entry_view_tracking]',//: :visible => false
        'dynamic_tracking_disabling': 'input[name=dynamic_tracking_disabling]'

      })
  }

  load() {
    this.get('settings_btn').click()
    this.get('wrap').find('div.sidebar a:contains("Hit Tracking")').click()
  }
}
export default HitTracking;