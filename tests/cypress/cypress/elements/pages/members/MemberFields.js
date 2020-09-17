import ControlPanel from '../ControlPanel'

class MemberFields extends ControlPanel {
  constructor() {
      super()

      this.elements({

        'member_actions': 'select[name=bulk_action]',// :visible => false
        'member_fields_table': 'table',
        'member_fields_create': 'fieldset.tbl-search a'
      })
    }

  load() {
    this.get('members_btn').click()
    this.get('wrap').find('a:contains("Member Fields")').click()
  }
}
export default MemberFields;