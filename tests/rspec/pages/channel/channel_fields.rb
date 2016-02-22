class ChannelFields < ControlPanelPage
  element :create_new, '.w-12 .tbl-ctrls a.btn'
  elements :fields, '.w-12 table tbody tr'
  elements :fields_edit, '.w-12 table tbody tr .edit a'
  elements :fields_checkboxes, '.w-12 table tbody tr input[type="checkbox"]'

  def load
    visit '/system/index.php?/cp/channels/fields/1'
  end
end
