module Profile
  class ProfileForm < SitePrism::Section
    element :profile_form, 'form[action*="cp/members/profile"]'
    element :profile_form_submit, 'form[action*="cp/members/profile"] div.form-btns.form-btns-top input[type=submit]'

    submit
      profile_form_submit.click
    }
  }
}
