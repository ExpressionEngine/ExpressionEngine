import ControlPanel from '../ControlPanel'
import './logs.js'
class CpLogs extends ControlPanel {
  constructor() {
    super()

    this.urlMatcher = /logs\/cp/

   }

  generate_data(
    count =  250,
    site_id = null,
    member_id = null,
    username = null,
    ip_address = null,
    timestamp_min = null,
    timestamp_max = null,
    action = null
    )
  
 {
 	let command = "cd fixtures && php cpLog.php"

 	 if (count){
      command += " --count " + count.toString()
    }

    if (site_id){
      command += " --site-id " + site_id.toString()
    }
    

    if (member_id){
      command += " --member-id " + member_id.toString()
    }
    

    if (username){
      command += " --username '" + username.toString() + "'"
    }
    

    if (ip_address){
      command += " --ip-address '" + ip_address.toString() + "'"
    }
    

    if (timestamp_min){
      command += " --timestamp-min " + timestamp_min.toString()
    }
    

    if (timestamp_max){
      command += " --timestamp-max " + timestamp_max.toString()
    }

    if (action){
      command += " --action '" + action.toString() + "'"
    }
    
    command += " > /dev/null 2>&1"

    cy.exec(command)

 }

}

 export default CpLogs;


  	
  	

