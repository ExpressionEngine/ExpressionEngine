class FieldGroups < ControlPanelPage
  element :create_new, '.sidebar a.btn.left'
  elements :field_groups, '.folder-list > li'
  elements :field_groups_edit, '.folder-list li.edit a'
  elements :field_groups_fields, '.folder-list > li > a'

  def load
    visit '/admin.php?/cp/fields'
  end
end
