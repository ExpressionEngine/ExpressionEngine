import ControlPanel from '../ControlPanel'

class MemberSettings extends ControlPanel {
  constructor() {
      super()

      this.elements({
        'allow_member_registration_toggle': '[data-toggle-for=allow_member_registration]',
        'allow_member_registration': 'input[name=allow_member_registration]',//visible => false
        'req_mbr_activation': 'input[name=req_mbr_activation]',
        'require_terms_of_service_toggle': '[data-toggle-for=require_terms_of_service]',
        'require_terms_of_service': 'input[name=require_terms_of_service]',//visible => false
        'allow_member_localization_toggle': '[data-toggle-for=allow_member_localization]',
        'allow_member_localization': 'input[name=allow_member_localization]',//visible => false
        'default_primary_role': 'input[type!=hidden][name=default_primary_role]',
        'member_theme': 'input[type!=hidden][name=member_theme]',
        'memberlist_order_by': 'input[type!=hidden][name=memberlist_order_by]',
        'memberlist_sort_order': 'input[type!=hidden][name=memberlist_sort_order]',
        'memberlist_row_limit': 'input[type!=hidden][name=memberlist_row_limit]',
        'new_member_notification_toggle': '[data-toggle-for=new_member_notification]',
        'new_member_notification': 'input[name=new_member_notification]',//visible => false
        'mbr_notification_emails': 'input[type!=hidden][name=mbr_notification_emails]'

      })
  }

  load() {
    this.get('settings_btn').click()
    this.get('wrap').find('div.sidebar a:contains("Member Settings")').click()
  }
}
export default MemberSettings;