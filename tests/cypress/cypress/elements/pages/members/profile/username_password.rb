require_relative '_profile_form.rb'

module Profile
  class UsernamePassword < ControlPanelPage
    set_url '/admin.php?/cp/members/profile/auth'

    section :profile_form,
      Profile::ProfileForm,
      'form[action*="cp/members/profile"]'

    element :username, 'input[name=username]'
    element :screen_name, 'input[name=screen_name]'
    element :password, 'input[name=password]'
    element :confirm_password, 'input[name=confirm_password]'
    element :current_password, 'input[name=verify_password]'
  end
end
