import ControlPanel from '../ControlPanel'

class UrlsSettings extends ControlPanel {
  constructor() {
      super()

      this.elements({
        'base_url': 'input[name=base_url]',
        'base_path': 'input[name=base_path]',
        'site_index': 'input[name=site_index]',
        'site_url': 'input[name=site_url]',
        'cp_url': 'input[name=cp_url]',
        'theme_folder_url': 'input[name=theme_folder_url]',
        'theme_folder_path': 'input[name=theme_folder_path]',
        'profile_trigger': 'input[name=profile_trigger]',
        'category_segment_trigger': 'input[name=reserved_category_word]',
        'use_category_name': 'input[name=use_category_name]',
        'url_title_separator': 'input[name=word_separator]'
      })
  }

  load() {
    this.get('settings_btn').click()
    this.get('wrap').find('div.sidebar a:contains("URL and Path Settings")').click()
  }
}
export default UrlsSettings;