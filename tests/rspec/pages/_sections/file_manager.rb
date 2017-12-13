class FileManagerPage < ControlPanelPage

  # Title/header box elements
  element :manager_title, '.section-header__title'
  element :title_toolbar, '.section-header__options'
  element :download_all, 'a.icon--export'
  # element :phrase_search, 'fieldset.tbl-search input[name=search]'
  # element :search_submit_button, 'fieldset.tbl-search input.submit'
  element :upload_new_file_button, '.section-header__controls a.filter-item__link--action'
  element :upload_new_file_filter, '.section-header__controls .filter-item'
  element :upload_new_file_filter_menu, '.section-header__controls .filter-submenu'
  elements :upload_new_file_filter_menu_items, '.section-header__controls .filter-submenu a'

  # Sidebar elements
  element :upload_directories_header, 'div.sidebar h2:first-child'
  element :new_directory_button, 'div.sidebar h2:first-child a.btn.action'
  element :watermarks_header, 'div.sidebar h2:nth-child(3)'
  element :new_watermark_button, 'div.sidebar h2:nth-child(3) a.btn.action'
  elements :folder_list, 'div.sidebar div.scroll-wrap ul.folder-list li'

end
