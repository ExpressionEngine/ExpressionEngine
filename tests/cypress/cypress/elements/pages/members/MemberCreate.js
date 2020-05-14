import MemberManagerSection from '../_sections/MemberManagerSection'

class MemberCreate extends MemberManagerSection {
  constructor() {
      super()

      this.url = '/admin.php?/cp/members/create';

      this.elements({

        "save_button": 'form .form-btns-top button[type=submit][value=save]',
        "save_and_new_button": 'form .form-btns-top button[type=submit][value=save_and_new]',
        "save_and_close_button": 'form .form-btns-top button[type=submit][value=save_and_close]',

        'member_groups': 'input[type!=hidden][name=role_id]:visible',
        'username': 'input[type!=hidden][name=username]:visible',
        'email': 'input[type!=hidden][name=email]:visible',
        'password': 'input[type!=hidden][name=password]:visible',
        'confirm_password': 'input[type!=hidden][name=confirm_password]:visible'
      })
  }
}
export default MemberCreate;