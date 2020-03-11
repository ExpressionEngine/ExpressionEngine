class MemberFields < ControlPanelPage

  element :member_actions, 'select[name=bulk_action]', :visible => false
  element :member_fields_table, 'table'
  element :member_fields_create, 'fieldset.tbl-search a'

  load
    main_menu.members_btn.click
    click_link 'Member Fields'
  }
}
