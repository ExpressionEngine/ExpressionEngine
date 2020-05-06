import ProfileForm from './ProfileForm'

class PersonalSettings extends ProfileForm {
  constructor() {
      super()
      this.url = '/admin.php?/cp/members/profile/settings'

      this.elements({

        'profile_form': 'form[action*="cp/members/profile"]',
        'profile_form_submit': 'form[action*="cp/members/profile"] .tab-bar__right-buttons .form-btns input[type=submit]'
      })
  }
}
export default PersonalSettings;