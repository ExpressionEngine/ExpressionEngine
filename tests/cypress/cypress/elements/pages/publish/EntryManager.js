import ControlPanel from '../ControlPanel'

class EntryManager extends ControlPanel {
  constructor() {
    super()
    this.url = '/admin.php?/cp/publish/edit&{perpage}&{filter_by_channel}';

    this.elements({
      'entry_rows': '.ee-main__content .tbl-ctrls form table tbody tr',
      'entry_checkboxes': '.ee-main__content .tbl-ctrls form table tbody tr input[type="checkbox"]',
      'center_modal': '.app-modal--center',
      'edit_menu': '.dropdown:contains("View All")'
    })

  }

  check_entry(title) {
    //this.get_row_for_title(title).find('input[type="checkbox"]').click();
    this.get_row_for_title(title).find('input[type="checkbox"]').check();
  }

  get_row_for_title(title) {
    cy.wait(300)//AJ
    return this.get('entry_rows').find('td:nth-child(2) a').filter(function(index) { return Cypress.$(this).text() === title; }).parent().parent()

  }

  click_edit_for_entry(title) {
    this.get('entry_rows').find('td:nth-child(2) a').filter(function(index) { return Cypress.$(this).text() === title; }).click();
  }
}
export default EntryManager;