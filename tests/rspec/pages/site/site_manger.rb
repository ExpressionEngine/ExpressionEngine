class SiteManager < ControlPanelPage
  set_url_matcher /msm/

  element :settings_icon, '.section-header__options a.icon--settings'
  element :title, '.section-header__title'
  element :add_site_button, '.section-header__controls a.btn.action'

  sections :sites, 'table.app-listing tbody tr' do
    element :id, 'td:first-child'
    element :name, 'td:nth-child(2)'
    element :short_name, 'td:nth-child(3)'
    element :status, 'td:nth-child(4)'
    section :manage, 'td:nth-child(5)' do
      element :edit, '.toolbar .edit a'
    end
    element :bulk_action_checkbox, 'td:nth-child(6) input'
  end

  def load
    self.open_dev_menu
    click_link 'Site Manager'
  end
end
