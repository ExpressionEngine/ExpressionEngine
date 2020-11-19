import MemberManagerSection from '../_sections/MemberManagerSection'

class PendingMembers extends MemberManagerSection {
  constructor() {
      super()

      this.elements({

      })
    }

  load() {
    this.get('members_btn').click()
    this.get('wrap').find('a:contains("Pending Activation")').click()
  }
}
export default PendingMembers;