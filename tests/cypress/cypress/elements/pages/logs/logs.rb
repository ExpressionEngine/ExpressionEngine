class Logs < ControlPanelPage
  element :heading, 'div.w-12 div.box div.tbl-ctrls h1'

  element :keyword_search, 'input[name=filter_by_keyword]'
  element :submit_button, 'button.btn.action'
  elements :forms, 'div.w-12 form'

  element :username_filter, 'div.filters a[data-filter-label^="username"]'
  element :username_filter_menu, 'div.filters a[data-filter-label^="username"] + div.sub-menu ul'
  element :username_manual_filter, 'input[name="filter_by_username"]'

  # element :site_filter, 'div.filters ul li:nth-child(2)'
  # element :site_manual_filter, 'input[name="filter_by_site"]'

  element :date_filter, 'div.filters a[data-filter-label^="date"]'
  element :date_filter_menu, 'div.filters a[data-filter-label^="date"] + div.sub-menu ul'
  element :date_manual_filter, 'input[name="filter_by_date"]'

  element :perpage_filter, 'div.filters a[data-filter-label^="show"]'
  element :perpage_filter_menu, 'div.filters a[data-filter-label^="show"] + div.sub-menu ul'
  element :perpage_manual_filter, 'input[name="perpage"]'

  element :no_results, 'p.no-results'
  element :remove_all, 'button.btn.action'

  elements :items, 'section.item-wrap div.item'

  attr_accessor :menu_item

  hide_filters
    find('div.filters a.open').click
  }

  load
    # Close the deprecation notice alert if it is there.
    begin
      find('div.alert.standard a.close')
      click_link 'Developer Log'
    rescue
    }
    open_dev_menu
    click_link 'Logs'
    click_link @menu_item, :href => /cp\/log/
  }
}
