import ProfileForm from './ProfileForm'

class UsernamePassword extends ProfileForm {
  constructor() {
      super()
      this.url = '/admin.php?/cp/members/profile/auth';

      this.elements({

        'profile_form': 'form[action*="cp/members/profile"]',
        'profile_form_submit': 'form[action*="cp/members/profile"] div.form-btns.form-btns-top input[type=submit]',

        'username': 'input[name=username]:visible',
        'screen_name': 'input[name=screen_name]:visible',
        'password': 'input[name=password]:visible',
        'confirm_password': 'input[name=confirm_password]:visible',
        'current_password': 'input[name=verify_password]:visible'

      })
  }
}
export default UsernamePassword;
