class TemplateManager < ControlPanelPage
  set_url_matcher /design/

  element :create_new_template_button, 'fieldset.right a.btn.action'

  element :prepage_filter, '.filters ul li:first-child a'

  sections :templates, '.tbl-wrap table tbody tr' do
    element :name, 'td:first-child'
    element :type, 'td:nth-child(2)'
    section :manage, 'td:nth-child(3)' do
      element :view, '.toolbar .view a'
      element :edit, '.toolbar .edit a'
      element :settings, '.toolbar .settings a'
    end
    element :bulk_action_checkbox, 'td:nth-child(4) input'
  end

  def load
    self.open_dev_menu
    click_link 'Templates'
  end

end
