import ControlPanel from '../ControlPanel'

class UrlsSettings extends ControlPanel {
  constructor() {
      super()

      this.elements({
        'base_url': 'input[type!=hidden][name=base_url]',
        'base_path': 'input[type!=hidden][name=base_path]',
        'site_index': 'input[type!=hidden][name=site_index]',
        'site_url': 'input[type!=hidden][name=site_url]',
        'cp_url': 'input[type!=hidden][name=cp_url]',
        'theme_folder_url': 'input[type!=hidden][name=theme_folder_url]',
        'theme_folder_path': 'input[type!=hidden][name=theme_folder_path]',
        'profile_trigger': 'input[type!=hidden][name=profile_trigger]',
        'category_segment_trigger': 'input[type!=hidden][name=reserved_category_word]',
        'use_category_name': 'input[type!=hidden][name=use_category_name]',
        'url_title_separator': 'input[type!=hidden][name=word_separator]'
      })
  }

  load() {
    this.get('settings_btn').click()
    this.get('wrap').find('div.sidebar a:contains("URL and Path Settings")').click()
  }
}
export default UrlsSettings;