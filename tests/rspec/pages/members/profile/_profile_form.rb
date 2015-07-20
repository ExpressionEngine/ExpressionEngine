module Profile
  class ProfileForm < SitePrism::Section
    element :profile_form, 'form.settings[action*="cp/members/profile"]'
    element :profile_form_submit, 'form.settings[action*="cp/members/profile"] input[value=btn_save_settings]'

    def submit
      profile_form_submit.click
    end
  end
end
