class EditFile < FileManagerPage
  set_url_matcher /files\/file\/edit/

  # Main box elements
  element :heading, 'div.form-standard form div.form-btns-top h1'
  element :crop_button, 'div.form-standard form div.form-btns-top h1 a.action'

  # Edit form
  element :title_input, 'fieldset input[name="title"]'
  element :description_input, 'fieldset textarea[name="description"]'
  element :credit_input, 'fieldset input[name="credit"]'
  element :location_input, 'fieldset input[name="location"]'
  element :form_submit_button, '.form-btns-top input[type="submit"]'

  def load
    click_link 'Files'
    find('div.box form table.app-listing tr:nth-child(2) td:nth-child(4) ul.toolbar li.edit').click
  end

end
