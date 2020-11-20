// import ControlPanel from '../ControlPanel'
// import './logs.js'
// class DeveloperLog extends ControlPanel {
//   constructor() {
//     super()

//     this.urlMatcher = 'admin.php?/cp/logs/developer';
//     this.count = 250;
//     this.timestamp_min = null;
//     this.timestamp_max = null;
//     this.description = null;


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

 
    

//    }

// runner(){
// 	let command = "cd fixtures && php developerLog.php"

// 	if (this.count > 0){
//       command += " --count " + count.toString()
	
//     }

//     if (this.timestamp_min){
//       command += " --timestamp-min " + timestamp_min.toString()
//     }
    

//     if (this.timestamp_max){
//       command += " --timestamp-max " + timestamp_max.toString()
//     }
    

//     if (this.description){
//       command += " --description '" + description.toString() + "'"
//     }
    

//     command += " > /dev/null 2>&1"
//     cy.exec(command)
//  }
// }
// export default DeveloperLog;



import ControlPanel from '../ControlPanel'

class DeveloperLog extends ControlPanel {
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
export default DeveloperLog;