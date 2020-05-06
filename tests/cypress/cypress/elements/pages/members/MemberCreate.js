import MemberManagerSection from '../_sections/MemberManagerSection'

class MemberCreate extends MemberManagerSection {
  constructor() {
      super()

      this.url = '/admin.php?/cp/members/create';

      this.elements({
        'save_button': 'form .tab-bar__right-buttons .form-btns button[type=submit][value=save]',
        'save_and_new_button': 'form .tab-bar__right-buttons .form-btns button[type=submit][value=save_and_new]',
        'save_and_close_button': 'form .tab-bar__right-buttons .form-btns button[type=submit][value=save_and_close]',

        'member_groups': 'input[name=group_id]:visible',
        'username': 'input[name=username]:visible',
        'email': 'input[name=email]:visible',
        'password': 'input[name=password]:visible',
        'confirm_password': 'input[name=confirm_password]:visible'
      })
  }
}
export default MemberCreate;