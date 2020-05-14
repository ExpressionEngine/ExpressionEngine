class StatusGroupCreate < ControlPanelPage

  element :group_name, 'input[type!=hidden][name=group_name]'

  load
    this.open_dev_menu()
    click_link 'Channels'
    click_link 'Status Groups'
    click_link 'Create New'
  }

  load_edit_for_status_group(number)
    this.open_dev_menu()
    click_link 'Channels'
    click_link 'Status Groups'

    find('tbody tr:nth-child('+number.to_s+') li.edit a').click
  }
}
