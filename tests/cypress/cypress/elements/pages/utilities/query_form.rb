class QueryForm < ControlPanelPage

  element :query_form, 'textarea[name=thequery]'

  load
    self.open_dev_menu
    click_link 'Utilities'
    click_link 'Query Form'
  }
}
