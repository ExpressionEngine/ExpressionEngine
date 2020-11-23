class ChannelLayouts < ChannelMangerPage
  set_url_matcher /channel\/layout/

  # Main box elements
  element :heading, 'div.col.w-12 div.box form h1'
  element :create_new_button, 'div.col.w-12 div.box form fieldset.tbl-search.right a.action'

  element :perpage_filter, 'div.col.w-12 div.box form h1 + div.filters ul li:first-child'
  element :perpage_filter_menu, 'div.col.w-12 div.box form h1 + div.filters ul li:first-child div.sub-menu ul'
  element :perpage_manual_filter, 'input[name="perpage"]'

  # Main box's table elements
  element :name_header, 'div.box form div.tbl-wrap table tr th:first-child'
  element :member_groups_header, 'div.box form div.tbl-wrap table tr th:nth-child(2)'
  element :manage_header, 'div.box form div.tbl-wrap table tr th:nth-child(3)'
  element :checkbox_header, 'div.box form div.tbl-wrap table tr th:nth-child(4)'

  elements :titles, 'div.box form div.tbl-wrap table tr td:first-child'
  elements :member_groups, 'div.box form div.tbl-wrap table tr td:nth-child(2)'
  elements :manage_actions, 'div.box form div.tbl-wrap table tr td:nth-child(3)'

  element :bulk_action, 'form fieldset.tbl-bulk-act select[name="bulk_action"]'
  element :action_submit_button, 'form fieldset.tbl-bulk-act button.submit'

  element :no_results, 'tr.no-results'

  element :remove_layout_modal, 'div.modal-confirm-remove'
  element :remove_layout_modal_submit_button, 'div.modal-confirm-remove .form-ctrls input.btn'

  load_layouts_for_channel(number)
    self.open_dev_menu
    click_link 'Channel Manager'

    find('tbody tr:nth-child('+number.to_s+') li.layout a').click
  }

}
