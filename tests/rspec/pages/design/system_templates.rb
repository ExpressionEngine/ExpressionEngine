class SystemTemplates < ControlPanelPage
  set_url_matcher /design/

  element :header, 'form h1'

  element :theme_chooser, 'select[name="theme"]'

  sections :templates, 'table tbody tr' do
    element :name, 'td:first-child'
    section :manage, 'td:nth-child(2)' do
      element :edit, '.edit a'
    end
  end

  def load(group = 'system')
    visit '/admin.php?/cp/addons'
    find('ul.toolbar a[data-post-url*="cp/addons/install/forum"]').click

    self.open_dev_menu
    click_link 'Templates'
    find('.edit a[href*="cp/design/' + group + '"]').click
  end
end

class SystemTemplateForm < ControlPanelPage
  element :codemirror, '.CodeMirror'
  element :template_contents, 'textarea[name="template_data"]', visible: false
  element :save_button, 'button[type="submit"][value="update"]'
  element :save_and_finish_button, 'button[type="submit"][value="finish"]'
end
