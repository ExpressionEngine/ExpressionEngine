import MemberManagerSection from '../_sections/MemberManagerSection'

class PendingMembers extends MemberManagerSection {
  constructor() {
      super()

      this.elements({

      })
    }

  load() {
    cy.visit('admin.php?/cp/members&role_filter=4')
  }
}
export default PendingMembers;