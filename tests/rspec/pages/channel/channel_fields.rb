class ChannelFields < ControlPanelPage
  element :create_new, '.section-header a.btn.action'
  elements :fields, '.tbl-list > li'
  elements :fields_edit, '.tbl-list > li .main > a'
  elements :fields_checkboxes, '.tbl-list > li input[type="checkbox"]'

  def load
    visit '/admin.php?/cp/fields'
  end
end
