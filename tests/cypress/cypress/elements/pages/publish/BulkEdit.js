import ControlPanel from '../ControlPanel'

class BulkEdit extends ControlPanel {
  constructor() {
      super()

      this.elements({

        'save_all_button': '.app-modal--center .form-btns-top .btn',
        'heading': '.app-modal--center h1',
        'filter_heading': '.app-modal--center [data-bulk-edit-entries-react] > div > h2',
        'filter_input': '.app-modal--center .field-search input',
        'selected_entries': '.app-modal--center .entry-list li',
        'selected_entries_note': '.app-modal--center .entry-list__note',
        'clear_all_link': '.app-modal--center .entry-list__note a',

        'add_field': '.app-modal--center .fluid-actions a.has-sub',
        'field_options': '.app-modal--center .fluid-actions .sub-menu li:visible a',
        'field_options_filter': '.app-modal--center .fluid-actions .filter-search input',
        'fluid_fields': '.app-modal--center .fluid-item:visible'
      })
  }
}
export default BulkEdit;