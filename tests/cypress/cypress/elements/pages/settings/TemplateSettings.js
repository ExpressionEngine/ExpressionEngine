import ControlPanel from '../ControlPanel'

class TemplateSettings extends ControlPanel {
  constructor() {
      super()

      this.elements({
        'strict_urls': 'input[name=strict_urls]', //visible => false
        'strict_urls_toggle': 'a[data-toggle-for=strict_urls]',
        'site_404': 'div[data-input-value="site_404"]',
        'site_404_options': 'div[data-input-value="site_404"] input[type="radio"]',
        'save_tmpl_revisions': 'input[name=save_tmpl_revisions]', //visible => false
        'save_tmpl_revisions_toggle': 'a[data-toggle-for=save_tmpl_revisions]',
        'max_tmpl_revisions': 'input[name=max_tmpl_revisions]'
      })
  }

  load() {
    this.get('settings_btn').click()
    this.get('wrap').find('div.sidebar a:contains("Template Settings")').click()
  }
}
export default TemplateSettings;