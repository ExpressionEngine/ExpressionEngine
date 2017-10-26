class UrlsSettings < ControlPanelPage

  element :base_url, 'input[name=base_url]'
  element :base_path, 'input[name=base_path]'
  element :site_index, 'input[name=site_index]'
  element :site_url, 'input[name=site_url]'
  element :cp_url, 'input[name=cp_url]'
  element :theme_folder_url, 'input[name=theme_folder_url]'
  element :theme_folder_path, 'input[name=theme_folder_path]'
  element :profile_trigger, 'input[name=profile_trigger]'
  element :category_segment_trigger, 'input[name=reserved_category_word]'
  elements :use_category_name, 'input[name=use_category_name]'
  elements :url_title_separator, 'input[name=word_separator]'

  def load
    settings_btn.click
    click_link 'URL and Path Settings'
  end
end
