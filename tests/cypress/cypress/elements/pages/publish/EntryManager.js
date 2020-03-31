import ControlPanel from '../ControlPanel'

class EntryManager extends ControlPanel {
  constructor() {
    super()
    this.url = '/admin.php?/cp/publish/edit{&perpage}{&filter_by_channel}';

    this.elements({
      'entry_rows': '.w-16 .tbl-ctrls form table tbody tr',
      'entry_checkboxes': '.w-16 .tbl-ctrls form table tbody tr input[type="checkbox"]',
      'center_modal': '.app-modal--center'
    })

  }

  check_entry(title) {
    this.get_row_for_title(title).find('input[type="checkbox"]').click();
  }

  get_row_for_title(title) {
    for (const row of this.get('entry_rows')) {
      if (row.find('td:nth-child(2) a').innerText == title) {
        return row;
      }
    }
  }

  click_edit_for_entry(title) {
    row = this.get_row_for_title(title);
    row.find('td:nth-child(2) a').click();
  }
}
export default EntryManager;