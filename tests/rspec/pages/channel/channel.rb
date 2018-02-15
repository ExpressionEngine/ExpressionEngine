class Channel < ControlPanelPage

  element :save_button, 'form .form-btns-top button[type=submit][value=save]'
  element :save_and_new_button, 'form .form-btns-top button[type=submit][value=save_and_new]'

  element :channel_tab, 'ul.tabs a[rel="t-0"]'
  element :fields_tab, 'ul.tabs a[rel="t-1"]'
  element :categories_tab, 'ul.tabs a[rel="t-2"]'
  element :statuses_tab, 'ul.tabs a[rel="t-3"]'
  element :settings_tab, 'ul.tabs a[rel="t-4"]'

  # Channel Tab
  element :channel_title, 'input[name=channel_title]'
  element :channel_name, 'input[name=channel_name]'
  element :max_entries, 'input[name=max_entries]'
  elements :duplicate_channel_prefs, 'input[name=duplicate_channel_prefs]'

  # Fields Tab
  element :title_field_label, 'input[name=title_field_label]', :visible => false
  elements :field_groups, 'div[data-input-value="field_groups"] input[type="checkbox"]', :visible => false
  element :add_field_group_button, 'div[data-input-value="field_groups"] + a.btn[rel=add_new]', :visible => false
  elements :custom_fields, 'div[data-input-value="custom_fields"] input[type="checkbox"]', :visible => false
  element :add_field_button, 'div[data-input-value="custom_fields"] + a.btn[rel=add_new]', :visible => false

  # Categories Tab
  elements :cat_group, 'div[data-input-value="cat_group"] input[type="checkbox"]', :visible => false
  element :add_cat_group_button, 'div[data-input-value="cat_group"] + a.btn[rel=add_new]', :visible => false

  # Statuses Tab
  elements :statuses, 'div[data-input-value="statuses"] input[type="checkbox"]', :visible => false
  element :add_status_button, 'div[data-input-value="statuses"] + a.btn[rel=add_new]', :visible => false

  # Settings Tab
  element :channel_description, 'textarea[name=channel_description]', :visible => false
  elements :channel_lang, 'input[name=channel_lang]', :visible => false

  element :channel_url, 'input[name=channel_url]', :visible => false
  element :comment_url, 'input[name=comment_url]', :visible => false
  element :search_results_url, 'input[name=search_results_url]', :visible => false
  element :rss_url, 'input[name=rss_url]', :visible => false
  element :preview_url, 'input[name=preview_url]', :visible => false

  element :default_entry_title, 'input[name=default_entry_title]', :visible => false
  element :url_title_prefix, 'input[name=url_title_prefix]', :visible => false
  elements :deft_status, 'input[name=deft_status]', :visible => false
  elements :deft_category, 'input[name=deft_category]', :visible => false
  elements :search_excerpt, 'div[data-input-value="search_excerpt"] input[type="radio"]', :visible => false

  elements :channel_html_formatting, 'input[name=channel_html_formatting]', :visible => false
  element :extra_publish_controls, 'a[data-toggle-for=extra_publish_controls]', :visible => false
  element :channel_allow_img_urls, 'a[data-toggle-for=channel_allow_img_urls]', :visible => false
  element :channel_auto_link_urls, 'a[data-toggle-for=channel_auto_link_urls]', :visible => false

  elements :default_status, 'input[name=default_status]', :visible => false
  elements :default_author, 'input[name=default_author]', :visible => false
  element :allow_guest_posts, 'a[data-toggle-for=allow_guest_posts]', :visible => false

  element :enable_versioning, 'a[data-toggle-for=enable_versioning]', :visible => false
  element :max_revisions, 'input[name=max_revisions]', :visible => false
  element :clear_versioning_data, 'input[name=clear_versioning_data]', :visible => false

  element :comment_notify_authors, 'a[data-toggle-for=comment_notify_authors]', :visible => false
  element :channel_notify, 'a[data-toggle-for=channel_notify]', :visible => false
  element :channel_notify_emails, 'input[name=channel_notify_emails]', :visible => false
  element :comment_notify, 'a[data-toggle-for=comment_notify]', :visible => false
  element :comment_notify_emails, 'input[name=comment_notify_emails]', :visible => false

  element :comment_system_enabled, 'a[data-toggle-for=comment_system_enabled]', :visible => false
  element :deft_comments, 'a[data-toggle-for=deft_comments]', :visible => false
  element :comment_require_membership, 'a[data-toggle-for=comment_require_membership]', :visible => false
  element :comment_require_email, 'a[data-toggle-for=comment_require_email]', :visible => false
  element :comment_moderate, 'a[data-toggle-for=comment_moderate]', :visible => false
  element :comment_max_chars, 'input[name=comment_max_chars]', :visible => false
  element :comment_timelock, 'input[name=comment_timelock]', :visible => false
  element :comment_expiration, 'input[name=comment_expiration]', :visible => false
  element :apply_expiration_to_existing, 'input[name=apply_expiration_to_existing]', :visible => false
  elements :comment_text_formatting, 'input[name=comment_text_formatting]', :visible => false
  elements :comment_html_formatting, 'input[name=comment_html_formatting]', :visible => false
  element :comment_allow_img_urls, 'a[data-toggle-for=comment_allow_img_urls]', :visible => false
  element :comment_auto_link_urls, 'a[data-toggle-for=comment_auto_link_urls]', :visible => false

  def load
    self.open_dev_menu
    click_link 'Channels'
    click_link 'New'
  end

  def load_edit_for_channel(number)
    self.open_dev_menu
    click_link 'Channels'

    find('ul.tbl-list li:nth-child('+number.to_s+') li.edit a').click
  end
end
