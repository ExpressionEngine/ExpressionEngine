class FieldGroupForm < ControlPanelPage
  element :name, 'input[name="group_name"]'
  element :submit, 'input[value="Save Field Group"]'

  def load
    visit '/system/index.php?/cp/channels/fields/groups/edit/1'
  end
end
