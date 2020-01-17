class TemplateCreate < ControlPanelPage
  set_url_matcher /design\/template\/create/

  element :save_button, '.form-btns-top button.btn[value="finish"]'
  element :save_and_edit_button, '.form-btns-top button.btn[value="edit"]'

  element :name, 'input[name="template_name"]'
  elements :type, 'input[name="template_type"]'
  elements :duplicate_existing_template, 'div[data-input-value="template_id"] input[type="radio"]'

  def load
    self.open_dev_menu
    click_link 'Templates'
    click_link 'Create New Template'
  end
end

class TemplateEdit < ControlPanelPage
  set_url_matcher /design\/template\/edit/

  element :view_rendered_button, '.form-btns-top a.btn.action'
  element :save_button, '.form-btns button.btn[value="edit"]'
  element :save_and_close_button, '.form-btns button.btn[value="finish"]'

  # Tabs
  element :edit_tab, 'ul.tabs a[rel="t-0"]'
  element :notes_tab, 'ul.tabs a[rel="t-1"]'
  element :settings_tab, 'ul.tabs a[rel="t-2"]'
  element :access_tab, 'ul.tabs a[rel="t-3"]'

  # Edit Tab
  element :codemirror, '.CodeMirror'
  element :template_data, 'textarea[name="template_data"]', :visible => false

  # Notes Tab
  element :template_notes, 'textarea[name="template_notes"]', :visible => false

  # Settings Tab
  element :name, 'input[name="template_name"]', :visible => false
  elements :type, 'input[name="template_type"]', :visible => false
  element :enable_caching, 'a[data-toggle-for="cache"]', :visible => false
  element :refresh_interval, 'input[name="refresh"]', :visible => false
  element :allow_php, 'a[data-toggle-for="allow_php"]', :visible => false
  elements :php_parse_stage, 'input[name="php_parse_location"]', :visible => false
  element :hit_counter, 'input[name="hits"]', :visible => false

  # Access Tab
  elements :allowed_roles, 'div[data-input-value="allowed_roles"] input[type="checkbox"]', :visible => false
  elements :no_access_redirect, 'div[data-input-value="no_auth_bounce"] input[type="radio"]', :visible => false
  element :enable_http_auth, 'a[data-toggle-for="enable_http_auth"]', :visible => false
  element :template_route, 'input[name="route"]', :visible => false
  element :require_all_variables, 'a[data-toggle-for="route_required"]', :visible => false

  def load_edit_for_template(id)
    self.open_dev_menu
    click_link 'Templates'
    find('.edit a[href*="cp/design/template/edit/' + id + '"]').click
  end
end
