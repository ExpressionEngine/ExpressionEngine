class StatusGroups < ControlPanelPage

  elements :status_groups, 'table tbody tr'
  elements :status_group_titles, 'table tr td:nth-child(2)'

  load
    self.open_dev_menu
    click_link 'Channel Manager'
    click_link 'Status Groups'
  }
}
