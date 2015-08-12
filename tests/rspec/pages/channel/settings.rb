class ChannelSettings < ControlPanelPage

	element :channel_description, 'textarea[name=channel_description]'
	element :channel_lang, 'select[name=channel_lang]'

	element :channel_url, 'input[name=channel_url]'
	element :comment_url, 'input[name=comment_url]'
	element :search_results_url, 'input[name=search_results_url]'
	element :rss_url, 'input[name=rss_url]'
	element :live_look_template, 'select[name=live_look_template]'

	element :default_entry_title, 'input[name=default_entry_title]'
	element :url_title_prefix, 'input[name=url_title_prefix]'
	element :deft_status, 'select[name=deft_status]'
	element :deft_category, 'select[name=deft_category]'
	element :search_excerpt, 'select[name=search_excerpt]'

	element :channel_html_formatting, 'select[name=channel_html_formatting]'
	elements :channel_allow_img_urls, 'input[name=channel_allow_img_urls]'
	elements :channel_auto_link_urls, 'input[name=channel_auto_link_urls]'

	element :default_status, 'select[name=default_status]'
	element :default_author, 'select[name=default_author]'
	elements :allow_guest_posts, 'input[name=allow_guest_posts]'

	elements :enable_versioning, 'input[name=enable_versioning]'
	element :max_revisions, 'input[name=max_revisions]'
	element :clear_versioning_data, 'input[name=clear_versioning_data]'

	elements :comment_notify_authors, 'input[name=comment_notify_authors]'
	elements :channel_notify, 'input[name=channel_notify]'
	element :channel_notify_emails, 'input[name=channel_notify_emails]'
	elements :comment_notify, 'input[name=comment_notify]'
	element :comment_notify_emails, 'input[name=comment_notify_emails]'

	elements :comment_system_enabled, 'input[name=comment_system_enabled]'
	element :apply_comment_enabled_to_existing, 'input[name=apply_comment_enabled_to_existing]'
	elements :deft_comments, 'input[name=deft_comments]'
	elements :comment_require_membership, 'input[name=comment_require_membership]'
	elements :comment_require_email, 'input[name=comment_require_email]'
	elements :comment_moderate, 'input[name=comment_moderate]'
	element :comment_max_chars, 'input[name=comment_max_chars]'
	element :comment_timelock, 'input[name=comment_timelock]'
	element :comment_expiration, 'input[name=comment_expiration]'
	element :apply_expiration_to_existing, 'input[name=apply_expiration_to_existing]'
	element :comment_text_formatting, 'select[name=comment_text_formatting]'
	element :comment_html_formatting, 'select[name=comment_html_formatting]'
	elements :comment_allow_img_urls, 'input[name=comment_allow_img_urls]'
	elements :comment_auto_link_urls, 'input[name=comment_auto_link_urls]'

	def load_settings_for_channel(number)
		self.open_dev_menu
		click_link 'Channel Manager'

		find('tbody tr:nth-child('+number.to_s+') li.settings a').click
	end
end
