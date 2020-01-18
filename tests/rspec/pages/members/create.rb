module Member
  # Member Registration
  class Create < ControlPanelPage
    set_url '/admin.php?/cp/members/create'

    element :save_button, 'form .form-btns-top button[type=submit][value=save]'
    element :save_and_new_button, 'form .form-btns-top button[type=submit][value=save_and_new]'
    element :save_and_close_button, 'form .form-btns-top button[type=submit][value=save_and_close]'

    element :username, 'input[name=username]'
    element :email, 'input[name=email]'
    element :password, 'input[name=password]'
    element :confirm_password, 'input[name=confirm_password]'

    element :member_tab, '.tabs li a[rel="t-0"]'
    element :roles_tab, '.tabs li a[rel="t-1"]'

    elements :primary_roles, 'input[name=role_id]'
    elements :role_groups, 'input[name=role_groups[]]'
    elements :roles, 'input[name=roles[]]'
  end
end
