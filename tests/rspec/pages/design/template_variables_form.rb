class TemplateVariableForm < ControlPanelPage
  set_url_matcher /design\/variables/

  element :save_button, '.form-btns-top input.btn'

  element :name, 'input[name="variable_name"]'
  element :contents, '.CodeMirror'
  element :contents_editor, '.CodeMirror textarea', :visible => false
  element :contents_textarea, 'textarea[name="variable_contents"]', :visible => false
  elements :site_id, 'input[type="radio"][name="site_id"]'
end
