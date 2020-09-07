import ProfileForm from './ProfileForm'

class UsernamePassword extends ProfileForm {
  constructor() {
      super()
      this.url = '/admin.php?/cp/members/profile/auth';

      this.elements({

        'profile_form': 'form[action*="cp/members/profile"]',
        'profile_form_submit': 'form[action*="cp/members/profile"] .tab-bar__right-buttons .form-btns input[type=submit]',

        'username': 'input[type!=hidden][name=username]:visible',
        'screen_name': 'input[type!=hidden][name=screen_name]:visible',
        'password': 'input[type!=hidden][name=password]:visible',
        'confirm_password': 'input[type!=hidden][name=confirm_password]:visible',
        'current_password': 'input[type!=hidden][name=verify_password]:visible'

      })
  }
}
export default UsernamePassword;
