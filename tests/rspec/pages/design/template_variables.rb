class TemplateVariables < ControlPanelPage
  set_url_matcher /design\/variables/

  element :create_new_button, 'fieldset.right a.btn.action'

  element :keyword_search, '.filters ul li:first-child input'
  element :perpage_filter, '.filters ul li:nth-child(2) a'

  sections :variables, 'table.app-listing tbody tr' do
    element :name, 'td:first-child'
    element :all_sites, 'td:nth-child(2)'
    section :manage, 'td:nth-child(3)' do
      element :edit, '.toolbar .edit a'
      element :find, '.toolbar .find a'
    end
    element :bulk_action_checkbox, 'td:nth-child(4) input'
  end

  def load
    self.open_dev_menu
    click_link 'Templates'
    click_link 'Template Variables'
  end

end
