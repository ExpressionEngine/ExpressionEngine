module Member
  # Member Registration
  class Create < ControlPanelPage
    set_url '/admin.php?/cp/members/create'

    element :save_button, 'form .form-btns-top button[type=submit][value=save]'
    element :save_and_new_button, 'form .form-btns-top button[type=submit][value=save_and_new]'
    element :save_and_close_button, 'form .form-btns-top button[type=submit][value=save_and_close]'

    elements :member_groups, 'input[name=group_id]'
    element :username, 'input[name=username]'
    element :email, 'input[name=email]'
    element :password, 'input[name=password]'
    element :confirm_password, 'input[name=confirm_password]'
  end
end
