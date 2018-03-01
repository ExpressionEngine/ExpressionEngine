class RTESettings < ControlPanelPage
  set_url_matcher /addons\/settings\/rte/

  element :title, '.section-header__title'
  elements :headings, 'form h1'

  # Settings View
  element :rte_enabled, 'input[name=rte_enabled]', :visible => false
  element :rte_enabled_toggle, 'a[data-toggle-for=rte_enabled]'
  elements :default_tool_set, 'input[name="rte_default_toolset_id"]'
  element :save_settings_button, 'div.form-btns.form-btns-top input.btn[type="submit"]'

  element :create_new_button, 'div.tbl-ctrls form fieldset.tbl-search a.btn.action'
  elements :tool_sets, 'div.tbl-ctrls form div.tbl-wrap table tr'
  element :tool_set_name_header, 'div.tbl-ctrls form div.tbl-wrap table tr th:first-child'
  element :status_header, 'div.tbl-ctrls form div.tbl-wrap table tr th:nth-child(2)'
  element :manage_header, 'div.tbl-ctrls form div.tbl-wrap table tr th:nth-child(3)'
  element :checkbox_header, 'div.tbl-ctrls form div.tbl-wrap table tr th:nth-child(4)'

  elements :tool_set_names, 'div.tbl-ctrls form div.tbl-wrap table tr td:first-child'
  elements :statuses, 'div.tbl-ctrls form div.tbl-wrap table tr td:nth-child(2)'

  element :bulk_action, 'form fieldset.tbl-bulk-act select[name="bulk_action"]'
  element :action_submit_button, 'form fieldset.tbl-bulk-act input.submit'

  # Tool Set View
  element :tool_set_name, 'input[name="toolset_name"]'
  elements :choose_tools, 'div[data-input-value="tools"] input[type="checkbox"]'
  element :tool_set_save_and_close_button, 'div.form-btns.form-btns-top button[value="save_and_close"]'
  element :tool_set_save_button, 'div.form-btns.form-btns-top button[value="save"]'

  def load
    self.open_dev_menu
    click_link 'Add-Ons'
    self.find('tr', :text => 'Rich Text Editor').find('ul.toolbar li.settings a').click
  end
end
