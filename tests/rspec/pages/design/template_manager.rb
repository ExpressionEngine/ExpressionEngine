class TemplateManager < ControlPanelPage
  set_url_matcher /design/

  element :create_new_template_button, 'fieldset.right a.btn.action'

  element :prepage_filter, '.filters ul li:first-child a'

  sections :template_groups, '.sidebar .scroll-wrap ul.folder-list[data-name="template-group"] > li' do
    element :name, 'a[href*="cp/design/manager"]'
    element :edit, '.toolbar .edit a'
    element :remove, '.toolbar .remove a'
  end

  element :default_template_group, '.sidebar .scroll-wrap ul.folder-list[data-name="template-group"] > li.default'

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
