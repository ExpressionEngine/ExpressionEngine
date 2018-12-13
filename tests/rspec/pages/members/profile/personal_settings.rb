require_relative '_profile_form.rb'

module Profile
  class PersonalSettings < ControlPanelPage
    set_url '/admin.php?/cp/members/profile/settings'
    section :profile_form,
      Profile::ProfileForm,
      'form[action*="cp/members/profile"]'
  end
end
