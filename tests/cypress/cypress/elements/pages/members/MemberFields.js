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
    this.get('members_btn').click()
    cy.dismissLicenseAlert()
    cy.get('a:contains("Member Fields")').first().click()
  }
}
export default MemberFields;