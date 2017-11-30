class TemplatePartialForm < ControlPanelPage
  set_url_matcher /design\/snippets/

  element :save_button, '.form-btns-top input.btn'

  element :name, 'input[name="snippet_name"]'
  element :contents, '.CodeMirror'
  element :contents_editor, '.CodeMirror textarea', :visible => false
  element :contents_textarea, 'textarea[name="snippet_contents"]', :visible => false
  elements :site_id, 'input[type="radio"][name="site_id"]'
end
