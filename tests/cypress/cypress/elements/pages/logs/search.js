// import ControlPanel from '../ControlPanel'
// import './logs.js'
// class SearchLogs extends ControlPanel {
//   constructor() {
//     super()

//     this.selectors = Object.assign(this.selectors, {
//               "heading" : 'div.w-12 div.box div.tbl-ctrls h1',
//               "username_filter": 'div.filters a[data-filter-label^="username"]',
//               "username_filter_menu": 'div.filters a[data-filter-label^="username"] + div.sub-menu ul',
//               "username_manual_filter": 'input[name="filter_by_username"]',
//               "date_filter": 'div.filters a[data-filter-label^="date"]',
//               "date_filter_menu": 'div.filters a[data-filter-label^="date"] + div.sub-menu ul',
//               "date_manual_filter": 'input[name="filter_by_date"]',
//               "perpage_filter": 'div.filters a[data-filter-label^="show"]',
//               "perpage_filter_menu": 'div.filters a[data-filter-label^="show"] + div.sub-menu ul',
//               "perpage_manual_filter": 'input[name="perpage"]',
//               "no_results": 'p.no-results',
//               "remove_all": 'button.btn.action',
//               "items": 'section.item-wrap div.item'
//         }) 
//     this.urlMatcher = 'admin.php?/cp/logs/search';
//     // this.urlMatcher = /logs\/search/;
//     this.count = 250;
//     this.site_id = null;
//     this.member_id = null;
//     this.screen_name = null;
//     this.ip_address = null;
//     this.timestamp_min = null;
//     this.timestamp_max = null;
//     this.Type = null;
//     this.terms = null;

//   }


//   runner(){
//   	let  command = "cd fixtures && php searchLog.php"
//   	if (this.count>0){
//       command += " --count " + this.count.toString()
//   	}
    

//     if (this.site_id != null){
//       command += " --site-id " + this.site_id.toString()
//     }
    

//     if (this.member_id != null){
//       command += " --member-id " + this.member_id.toString()
//     }
    

//     if (screen_name != null){
//       command += " --screen-name '" + this.screen_name.toString() +"'"
//     }
    

//     if (this.ip_address != null){
//       command += " --ip-address '" + this.ip_address.toString()
//     }
    

//     if (this.timestamp_min != null){
//       command += " --timestamp-min " + this.timestamp_min.toString()
//     }
    

//     if (this.timestamp_max != null){
//       command += " --timestamp-max " + this.timestamp_max.toString()
//     }
    

//     if (this.Type != null){
//       command += " --type '" + this.Type.toString()
//     }
    

//     if (this.terms != null){
//       command += " --terms '" + this.terms.toString()
//     }
//     command += " > /dev/null 2>&1"
//     cy.exec(command)
//   }

// }
// export default SearchLogs;


import ControlPanel from '../ControlPanel'

class SearchLogs extends ControlPanel {
  constructor() {
    super()
    this.elements({
      'username' : 'a[data-filter-label="username"]',
      'filter_user' : 'input[name="filter_by_username"]',

      'date' : 'a[data-filter-label="date"]',

      'show' : 'a[data-filter-label="show"]',
      'custom_limit' : 'input[name="perpage"]',

      'search' : 'input[name="filter_by_keyword"]',

      'delete_all' : 'button[rel="modal-confirm-all"]',

     'list' : 'section[class="item-wrap log"]',

     'empty' : 'p[class="no-results"]',
     'confirm' : 'input[type="submit"]',


    

    
    })
  }
}
export default SearchLogs;


