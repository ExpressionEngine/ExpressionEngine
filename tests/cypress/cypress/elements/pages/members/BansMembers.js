import MemberManagerSection from '../_sections/MemberManagerSection'

class BansMembers extends MemberManagerSection {
  constructor() {
      super()

      this.elements({
        'banned_ips': 'textarea[name=banned_ips]',
        'banned_emails': 'textarea[name=banned_emails]',
        'banned_usernames': 'textarea[name=banned_usernames]',
        'banned_screen_names': 'textarea[name=banned_screen_names]',
        'ban_action': 'div[data-input-value="ban_action"]',
        'ban_action_options': 'div[data-input-value="ban_action"] input[type="radio"]',
        'ban_message': 'textarea[name=ban_message]',
        'ban_destination': 'input[type!=hidden][name=ban_destination]'
      })
    }

  load() {
    this.get('members_btn').click()
    this.get('wrap').find('a:contains("Ban Settings")').click()
  }
}
export default BansMembers;