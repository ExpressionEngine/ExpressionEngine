class TemplateRoutes < ControlPanelPage
  set_url_matcher /design\/routes/

  sections :routes, 'table tbody tr.setting-field' do
    element :reorder, 'td:first-child'
    element :template, 'td:nth-child(2)'
    element :group, 'td:nth-child(3)'
    element :route, 'td:nth-child(4) input'
    element :segments_required, 'td:nth-child(5) span.slider'
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

end
