class SiteForm < ControlPanelPage
  set_url_matcher /msm\/edit/

  element :settings_icon, '.section-header__options a.icon--settings'
  element :title, '.section-header__title'
  element :add_site_button, '.section-header__controls a.btn.action'

  element :save_button, '.form-btns-top input.btn'
  element :name, 'input[name="site_label"]'
  element :short_name, 'input[name="site_name"]'
  element :online, 'a[data-toggle-for="is_site_on"]'
  element :description, 'textarea[name="site_description"]'

  def add_site(options = {})
    defaults = {
      name: 'Rspec Site',
      short_name: 'rspec_site',
      description: 'Lorem ipsum...'
    }

    options = defaults.merge(options)

    name.set options[:name]
    short_name.set options[:short_name]
    description.set options[:description]
    save_button.click
  end

  def load_edit_for_site(id)
    self.open_dev_menu
    click_link 'Site Manager'
    find('a[href*="cp/msm/edit/' + id + '"]').click
  end
end
