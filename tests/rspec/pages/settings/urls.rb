class UrlsSettings < ControlPanelPage

	element :site_index, 'input[name=site_index]'
	element :site_url, 'input[name=site_url]'
	element :cp_url, 'input[name=cp_url]'
	element :theme_folder_url, 'input[name=theme_folder_url]'
	element :theme_folder_path, 'input[name=theme_folder_path]'
	element :doc_url, 'input[name=doc_url]'
	element :profile_trigger, 'input[name=profile_trigger]'
	element :category_segment_trigger, 'input[name=reserved_category_word]'
	elements :category_url, 'input[name=use_category_name]'
	element :url_title_separator, 'select[name=word_separator]'

	def load
		settings_btn.click
		click_link 'URL and Path Settings'
	end
end
