import ControlPanel from '../ControlPanel'

class MemberSettings extends ControlPanel {
  constructor() {
      super()

      this.elements({
        'allow_member_registration_toggle': 'a[data-toggle-for=allow_member_registration]',
        'allow_member_registration': 'input[name=allow_member_registration]',//visible => false
        'req_mbr_activation': 'input[name=req_mbr_activation]',
        'require_terms_of_service_toggle': 'a[data-toggle-for=require_terms_of_service]',
        'require_terms_of_service': 'input[name=require_terms_of_service]',//visible => false
        'allow_member_localization_toggle': 'a[data-toggle-for=allow_member_localization]',
        'allow_member_localization': 'input[name=allow_member_localization]',//visible => false
        'default_member_group': 'input[name=default_member_group]',
        'member_theme': 'input[name=member_theme]',
        'memberlist_order_by': 'input[name=memberlist_order_by]',
        'memberlist_sort_order': 'input[name=memberlist_sort_order]',
        'memberlist_row_limit': 'input[name=memberlist_row_limit]',
        'new_member_notification_toggle': 'a[data-toggle-for=new_member_notification]',
        'new_member_notification': 'input[name=new_member_notification]',//visible => false
        'mbr_notification_emails': 'input[name=mbr_notification_emails]'

      })
  }

  load() {
    this.get('settings_btn').click()
    this.get('wrap').find('div.sidebar a:contains("Members")').click()
  }
}
export default MemberSettings;