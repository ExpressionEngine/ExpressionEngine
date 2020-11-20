class StatusGroupCreate < ControlPanelPage

  element :group_name, 'input[name=group_name]'

  load
    self.open_dev_menu
    click_link 'Channel Manager'
    click_link 'Status Groups'
    click_link 'Create New'
  }

  load_edit_for_status_group(number)
    self.open_dev_menu
    click_link 'Channel Manager'
    click_link 'Status Groups'

    find('tbody tr:nth-child('+number.to_s+') li.edit a').click
  }
}
