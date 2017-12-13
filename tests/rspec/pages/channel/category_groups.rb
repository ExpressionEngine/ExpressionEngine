class CategoryGroups < ControlPanelPage

  element :table_list, '.tbl-list-wrap'
  elements :category_groups, '.folder-list li'
  elements :group_names, '.folder-list > li > a'

  def load
    self.open_dev_menu
    click_link 'Categories'
  end
end
