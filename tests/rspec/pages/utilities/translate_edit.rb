class TranslateEdit < ControlPanelPage
  set_url_matcher /utilities\/translate\/\w+\/edit/

  element :heading, 'div.w-12 form h1'

  elements :items, 'form fieldset'

  element :submit_button, 'form div.form-btns.form-btns-top input[type="submit"]'

  def load
    self.open_dev_menu
    click_link 'Utilities'
    click_link 'English'
    self.all('ul.toolbar li.edit a')[0].click # The addons_lang.php edit link
  end
end
