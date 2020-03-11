class BulkEdit < ControlPanelPage
  element :save_all_button, '.app-modal--center .form-btns-top .btn'
  element :heading, '.app-modal--center h1'
  element :filter_heading, '.app-modal--center [data-bulk-edit-entries-react] > div > h2'
  element :filter_input, '.app-modal--center .field-search input'
  elements :selected_entries, '.app-modal--center .entry-list li'
  element :selected_entries_note, '.app-modal--center .entry-list__note'
  element :clear_all_link, '.app-modal--center .entry-list__note a'

  element :add_field, '.app-modal--center .fluid-actions a.has-sub'
  elements :field_options, '.app-modal--center .fluid-actions .sub-menu li a'
  element :field_options_filter, '.app-modal--center .fluid-actions .filter-search input'
  elements :fluid_fields, '.app-modal--center .fluid-item'
}
