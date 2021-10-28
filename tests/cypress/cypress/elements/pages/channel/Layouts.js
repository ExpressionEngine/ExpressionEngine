class ChannelLayouts < ChannelMangerPage
  set_url_matcher /channel\/layout/

  # Main box elements
  element :heading, '.ee-main .title-bar .title-bar__title'
  element :create_new_button, 'div.col.w-12 div.box form fieldset.tbl-search.right a.action'

  element :perpage_filter, '.ee-main .title-bar .title-bar__title + div.filters ul li:first-child'
  element :perpage_filter_menu, '.ee-main .title-bar .title-bar__title + div.filters ul li:first-child div.sub-menu ul'
  element :perpage_manual_filter, 'input[type!=hidden][name="perpage"]'

  # Main box's table elements
  element :name_header, '.ee-main__content form .table-responsive table tr th:first-child'
  element :member_groups_header, '.ee-main__content form .table-responsive table tr th:nth-child(2)'
  element :manage_header, '.ee-main__content form .table-responsive table tr th:nth-child(3)'
  element :checkbox_header, '.ee-main__content form .table-responsive table tr th:nth-child(4)'

  elements :titles, '.ee-main__content form .table-responsive table tr td:first-child'
  elements :member_groups, '.ee-main__content form .table-responsive table tr td:nth-child(2)'
  elements :manage_actions, '.ee-main__content form .table-responsive table tr td:nth-child(3)'

  element :bulk_action, 'form fieldset.bulk-action-bar select[name="bulk_action"]'
  element :action_submit_button, 'form fieldset.bulk-action-bar button'

  element :no_results, 'tr.no-results'

  element :remove_layout_modal, 'div.modal-confirm-remove'
  element :remove_layout_modal_submit_button, 'div.modal-confirm-remove .form-ctrls .button'

  load_layouts_for_channel(number)
    this.open_dev_menu()
    click_link 'Channels'

    find('tbody tr:nth-child('+number.to_s+') li.layout a').click
  }

}
