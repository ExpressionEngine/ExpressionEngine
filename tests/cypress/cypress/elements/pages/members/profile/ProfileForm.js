import MemberManagerSection from '../../_sections/MemberManagerSection'

class ProfileForm extends MemberManagerSection {
  constructor() {
    super()

  }
  submit() {
    this.get('profile_form_submit').click()
  }
}
export default ProfileForm;