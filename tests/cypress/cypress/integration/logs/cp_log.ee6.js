import ControlPanel from '../ControlPanel'

class CpLogs extends ControlPanel {
  constructor() {
    super()
    this.elements({
      'username' : 'button[data-filter-label="username"]',
      'filter_user' : 'input[name="filter_by_username"]',

      'date' : 'button[data-filter-label="date"]',

      'show' : 'button[data-filter-label="show"]',
      'custom_limit' : 'input[name="perpage"]',

      'search' : 'input[name="filter_by_keyword"]',

      'delete_all' : 'button[rel="modal-confirm-all"]',

     'list' : 'div[class="list-group"]',

     'empty' : 'p[class="no-results"]',
     'confirm' : 'input[type="submit"]'



    
    })
  }
}
export default CpLogs;