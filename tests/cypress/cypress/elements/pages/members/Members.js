import MemberManagerSection from '../_sections/MemberManagerSection'

class Members extends MemberManagerSection {
  constructor() {
      super()
  }

  load () {
    this.get('members_btn').click()
  }
}
export default Members;