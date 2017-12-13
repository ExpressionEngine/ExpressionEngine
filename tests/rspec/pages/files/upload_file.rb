class UploadFile < FileManagerPage
  set_url_matcher /files\/upload/

  # Main box elements
  element :heading, 'div.form-standard form div.form-btns-top h1'

  # Edit form
  element :file_input, 'div.col.w-12 div.form-standard form fieldset input[name="file"]'
  element :title_input, 'div.col.w-12 div.form-standard form fieldset input[name="title"]'
  element :description_input, 'div.col.w-12 div.form-standard form fieldset textarea[name="description"]'
  element :credit_input, 'div.col.w-12 div.form-standard form fieldset input[name="credit"]'
  element :location_input, 'div.col.w-12 div.form-standard form fieldset input[name="location"]'
  element :form_submit_button, 'div.form-standard form div.form-btns-top input[type="submit"]'

  def load
    click_link 'Files'
    click_link 'Upload File'
    within '.section-header__controls .filter-submenu' do
      click_link 'Main Upload Directory'
    end
  end

end
