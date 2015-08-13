class Logs < ControlPanelPage
  element :heading, 'div.w-12 div.box form h1'

  element :phrase_search, 'input[name=search]'
  element :submit_button, 'input.submit'
  element :form, 'div.w-12 form'

  element :username_filter, 'div.filters a[data-filter-label^="username"]'
  element :username_filter_menu, 'div.filters a[data-filter-label^="username"] + div.sub-menu ul', visible: false
  element :username_manual_filter, 'input[name="filter_by_username"]', visible: false

  # element :site_filter, 'div.filters ul li:nth-child(2)'
  # element :site_manual_filter, 'input[name="filter_by_site"]', visible: false

  element :date_filter, 'div.filters a[data-filter-label^="date"]'
  element :date_filter_menu, 'div.filters a[data-filter-label^="date"] + div.sub-menu ul', visible: false
  element :date_manual_filter, 'input[name="filter_by_date"]', visible: false

  element :perpage_filter, 'div.filters a[data-filter-label^="show"]'
  element :perpage_filter_menu, 'div.filters a[data-filter-label^="show"] + div.sub-menu ul', visible: false
  element :perpage_manual_filter, 'input[name="perpage"]', visible: false

  element :no_results, 'p.no-results'
  element :remove_all, 'button.btn.remove'

  elements :items, 'section.item-wrap div.item'

  attr_accessor :menu_item

  def load
    open_dev_menu
    click_link 'Logs'
    click_link @menu_item
  end
end
