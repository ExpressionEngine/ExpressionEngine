import ControlPanel from '../ControlPanel'
import Logs from './logs.js'
class ThrottleLog extends ControlPanel {
  constructor() {
    super()

    this.urlMatcher = 'admin.php?/cp/logs/throttle';
    this.count = 250;
    this.ip_address = null;
    this.timpstamp_min = null;
    this.hits = null;
    this.locked_out = null;
  }



  runner(){
    let command = "php throttlingLog.php";
    if(this.count > 0){
      command += " --count " + this.count.toString();
    }

    command += " > /dev/null 2>&1";

    console.log(command);

    cy.exec(command);

  }

//   generate_data(
//     count = 250,
//     ip_address = null,
//     timestamp_min = null,
//     timestamp_max = null,
//     hits = null,
//     locked_out = null
//     )
  
// {
//   	let command = "cd fixtures && php throttlingLog.php"

//   	if (this.count){
//       command += " --count " + count.toString()  	}
    

//     if (this.ip_address){
//       command += " --ip-address '" + ip_address.toString() + "'"
//     }
    

//     if (this.timestamp_min){
//       command += " --timestamp-min " + timestamp_min.toString()    }
    

//     if (this.timestamp_max){
//       command += " --timestamp-max " + timestamp_max.toString()    }
    

//     if (this.hits){
//       command += " --hits " + hits.toString()    }
    

//     if (this.locked_out){
//       command += " --locked-out"
//     }
    

//     command += " > /dev/null 2>&1"
//     cy.exec(command)

//   }

}
export default ThrottleLog;