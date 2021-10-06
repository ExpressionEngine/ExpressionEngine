import ControlPanel from '../ControlPanel'

class SearchLogs extends ControlPanel {
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
     'confirm' : '[type="submit"]',


    //for creating member
     "save_button": 'form .form-btns-top button[type=submit][value=save]',
        "save_and_new_button": 'form .form-btns-top button[type=submit][value=save_and_new]',
        "save_and_close_button": 'form .form-btns-top button[type=submit][value=save_and_close]',

        'member_groups': 'input[type!=hidden][name=role_id]:visible',
        'usernamem': 'input[type!=hidden][name=username]:visible',
        'email': 'input[type!=hidden][name=email]:visible',
        'password': 'input[type!=hidden][name=password]:visible',
        'confirm_password': 'input[type!=hidden][name=confirm_password]:visible'



    
    })
  }
}
export default SearchLogs;
