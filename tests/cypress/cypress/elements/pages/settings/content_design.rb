class ContentDesign < ControlPanelPage

  element :new_posts_clear_caches_toggle, 'a[data-toggle-for=new_posts_clear_caches]'
  element :new_posts_clear_caches, 'input[name=new_posts_clear_caches]', :visible => false
  element :enable_sql_caching_toggle, 'a[data-toggle-for=enable_sql_caching]'
  element :enable_sql_caching, 'input[name=enable_sql_caching]', :visible => false
  element :auto_assign_cat_parents_toggle, 'a[data-toggle-for=auto_assign_cat_parents]'
  element :auto_assign_cat_parents, 'input[name=auto_assign_cat_parents]', :visible => false
  elements :image_resize_protocol, 'input[name=image_resize_protocol]'
  element :image_library_path, 'input[name=image_library_path]'
  element :thumbnail_suffix, 'input[name=thumbnail_prefix]'
  element :enable_emoticons_toggle, 'a[data-toggle-for=enable_emoticons]'
  element :enable_emoticons, 'input[name=enable_emoticons]', :visible => false
  element :emoticon_url, 'input[name=emoticon_url]'

  load
    settings_btn.click
    click_link 'Content & Design'
  }
}
