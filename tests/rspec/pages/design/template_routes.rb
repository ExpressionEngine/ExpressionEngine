class TemplateRoutes < ControlPanelPage
  set_url_matcher /design\/routes/

  sections :routes, 'table tbody tr.setting-field' do
    element :reorder, 'td:first-child .reorder'
    element :template, 'td:nth-child(2)'
    elements :template_choice, 'td:nth-child(2) div[data-input-value*="routes[rows]"] input[type="radio"]'
    element :group, 'td:nth-child(3)'
    element :route, 'td:nth-child(4) input'
    element :segments_required, 'td:nth-child(5) a[data-toggle-for=required]'
    element :delete, 'td:nth-child(6) a[rel=remove_row]'
  end

  element :no_results, 'tr.no-results'

  element :new_route_button, 'table tr.tbl-action td a.btn.action'
  element :update_button, 'fieldset.tbl-bulk-act input[type=submit]'

  def load
    self.open_dev_menu
    click_link 'Templates'
    click_link 'Template Routes'
  end

  def add_route(options = {})
    defaults = {
      template: '1',
      route: 'foo/bar',
      segments_required: false
    }

    options = defaults.merge(options)

    new_route_button.click
    route = routes.last

    route.template_choice.choose_radio_option options[:template]
    route.route.set options[:route]
    if options[:segments_required]
      route.segments_required.click if route.segments_required[:class].include? 'off'
    else
      route.segments_required.click if route.segments_required[:class].include? 'on'
    end
  end

end
