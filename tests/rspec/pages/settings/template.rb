class TemplateSettings < ControlPanelPage

  element :strict_urls, 'input[name=strict_urls]', :visible => false
  element :strict_urls_toggle, 'a[data-toggle-for=strict_urls]'
  element :site_404, 'div[data-input-value="site_404"]'
  elements :site_404_options, 'div[data-input-value="site_404"] input[type="radio"]'
  element :save_tmpl_revisions, 'input[name=save_tmpl_revisions]', :visible => false
  element :save_tmpl_revisions_toggle, 'a[data-toggle-for=save_tmpl_revisions]'
  element :max_tmpl_revisions, 'input[name=max_tmpl_revisions]'

  def load
    settings_btn.click
    click_link 'Template Settings'
  end
end
