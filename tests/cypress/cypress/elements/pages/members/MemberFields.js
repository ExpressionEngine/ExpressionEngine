import ControlPanel from '../ControlPanel'

class MemberFields extends ControlPanel {
  constructor() {
      super()

      this.elements({

        'member_actions': 'select[name=bulk_action]',// :visible => false
        'member_fields_table': 'table',
        'member_fields_create': '.title-bar__extra-tools .button--primary'
      })
    }

  load() {
    cy.visit('admin.php?/cp/settings/members')
  }
}
export default MemberFields;