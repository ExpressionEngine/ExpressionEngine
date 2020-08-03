import ControlPanel from '../ControlPanel'
import './logs.js'
class CpLogs extends ControlPanel {
  constructor() {
    super()

    this.urlMatcher = 'admin.php?/cp/logs/cp';
    
    this.count =  250;
    this.site_id = null;
    this.member_id = null;
    this.username = null;
    this.ip_address = null;
    this.timestamp_min = null;
    this.timestamp_max = null;
    this.Action = null;

   }


  
 runner(){
  let command = "cd fixtures && php cpLog.php"

   if (this.count){
      command += " --count " + this.count.toString()
    }

    if (this.site_id){
      command += " --site-id " + this.site_id.toString()
    }
    

    if (this.member_id){
      command += " --member-id " + this.member_id.toString()
    }
    

    if (this.username){
      command += " --username '" + this.username.toString() + "'"
    }
    

    if (this.ip_address){
      command += " --ip-address '" + this.ip_address.toString() + "'"
    }
    

    if (this.timestamp_min){
      command += " --timestamp-min " + this.timestamp_min.toString()
    }
    

    if (this.timestamp_max){
      command += " --timestamp-max " + this.timestamp_max.toString()
    }

    if (this.Action){
      command += " --action '" + this.Action.toString() + "'"
    }
    
    command += " > /dev/null 2>&1"

    cy.exec(command)

 }

}

 export default CpLogs;