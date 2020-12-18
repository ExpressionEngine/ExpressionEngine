import ControlPanel from '../ControlPanel'

class BulkEdit extends ControlPanel {
  constructor() {
      super()

      this.elements({

        'save_all_button': '.app-modal--center .form-btns-top .button',
        'heading': '.app-modal--center h1',
        'filter_heading': '.app-modal--center [data-bulk-edit-entries-react] div h2',
        'filter_input': '.app-modal--center [data-bulk-edit-entries-react] input[type=text]',
        'selected_entries': '.app-modal--center [data-bulk-edit-entries-react] .list-group li',
        'selected_entries_note': '.app-modal--center [data-bulk-edit-entries-react] .meta-info',
        'clear_all_link': '.app-modal--center [data-bulk-edit-entries-react] .meta-info a',

        'add_field': '.app-modal--center .fluid .fluid__footer .button',
        'field_options': '.app-modal--center .fluid .fluid__footer .button:visible',
        'field_options_filter': '.app-modal--center .fluid-actions input[type=text]',
        'fluid_fields': '.app-modal--center .fluid__item:visible'
      })
  }
}
export default BulkEdit;