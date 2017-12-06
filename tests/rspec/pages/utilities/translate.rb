class Translate < ControlPanelPage
  set_url_matcher /utilities\/translate/

  elements :languages, 'div.sidebar ul:nth-child(4) li'

  element :heading, 'div.w-12 form h1'

  element :phrase_search, 'form fieldset.tbl-search input[name=search]'
  element :search_submit_button, 'form fieldset.tbl-search input.submit'

  element :table, 'form table'
  element :no_results, 'form table tr.no-results'
  elements :rows, 'form table tr'

  element :bulk_action, 'form fieldset.tbl-bulk-act select[name="bulk_action"]'
  element :action_submit_button, 'form fieldset.tbl-bulk-act input.submit'

  def load
    self.open_dev_menu
    click_link 'Utilities'
    click_link 'English'
  end
end
