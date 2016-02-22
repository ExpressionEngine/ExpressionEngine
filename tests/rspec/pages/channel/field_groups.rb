class FieldGroups < ControlPanelPage
  element :create_new, '.w-12 .tbl-ctrls a.btn'
  elements :field_groups, '.w-12 table tbody tr'
  elements :field_groups_edit, '.w-12 table tbody tr .edit a'
  elements :field_groups_fields, '.w-12 table tbody tr .txt-only a'
  elements :field_groups_checkboxes, '.w-12 table tbody tr input[type="checkbox"]'

  def load
    visit '/system/index.php?/cp/channels/fields/groups/'
  end
end
