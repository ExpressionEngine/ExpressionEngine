class TemplateGroupCreate < ControlPanelPage
  set_url_matcher /design\/group\/create/

  element :save_button, '.form-btns-top input.btn'

  element :name, 'input[name=group_name]'
  elements :duplicate_existing_group, 'div[data-input-value="duplicate_group"] input[type="radio"]'
  element :make_default_group, 'a[data-toggle-for="make_default_group"]'

  def load
    self.open_dev_menu
    click_link 'Templates'
    find('a[href*="cp/design/group/create"]').click
  end
end

class TemplateGroupEdit < ControlPanelPage
  set_url_matcher /design\/group\/edit/

  element :save_button, '.form-btns-top input.btn'

  element :name, 'input[name=group_name]'
  element :make_default_group, 'a[data-toggle-for="make_default_group"]'

  def load_edit_for_group(name)
    self.open_dev_menu
    click_link 'Templates'
    find('a[href*="cp/design/group/edit/' + name + '"]').click
  end
end
