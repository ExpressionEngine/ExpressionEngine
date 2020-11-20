// import ControlPanel from '../ControlPanel'
// import Logs from './logs.js'
// class ThrottleLog extends ControlPanel {
//   constructor() {
//     super()

//     this.urlMatcher = 'admin.php?/cp/logs/throttle';
//     this.count = 250;
//     this.ip_address = null;
//     this.timestamp_min = null;
//     this.timestamp_max = null;
//     this.hits = null;
//     this.locked_out = null;
//   }

//   runner(){
//     let command = `cd support/fixtures && php throttlingLog.php `;
//     if(this.count > 0){
//       command += ` --count ` + this.count.toString();
//     }

//     if(this.ip_address != null){
//       command += `--ip-address ` + this.ip_address.toString();
//     }

//     if(this.timestamp_min != null){
//       command += ` --timestamp-min ` + timestamp_min.toString()
//     }
//     if (this.timestamp_max){
//      command += ` --timestamp-max ` + timestamp_max.toString()    }


//      if (this.hits){
//       command += ` --hits ` + hits.toString()    }
      

//       if (this.locked_out){
//         command += ` --locked-out`
//       }
//       command += ` > /dev/null 2>&1`;
//       cy.exec(command);
//  }


// }
// export default ThrottleLog;



import ControlPanel from '../ControlPanel'

class ThrottleLog extends ControlPanel {
  constructor() {
    super()
    this.elements({
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
export default ThrottleLog;


