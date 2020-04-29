import ControlPanel from '../ControlPanel'
import './logs.js'
class SearchLogs extends ControlPanel {
  constructor() {
    super()

    this.urlMatcher = /logs\/search/

  }

  generate_data(
    count = 250,
    site_id = null,
    member_id = null,
    screen_name = null,
    ip_address = null,
    timestamp_min = null,
    timestamp_max = null,
    type = null,
    terms = null
    )

  {
  	let  command = "cd fixtures && php searchLog.php"
  	if (count){
      command += " --count " + count.to_s
  	}
    

    if (site_id){
      command += " --site-id " + site_id.to_s
    }
    

    if (member_id){
      command += " --member-id " + member_id.to_s
    }
    

    if (screen_name){
      command += " --screen-name '" + screen_name.to_s + "'"
    }
    

    if (ip_address){
      command += " --ip-address '" + ip_address.to_s + "'"
    }
    

    if (timestamp_min){
      command += " --timestamp-min " + timestamp_min.to_s
    }
    

    if (timestamp_max){
      command += " --timestamp-max " + timestamp_max.to_s
    }
    

    if (type){
      command += " --type '" + type.to_s + "'"
    }
    

    if (terms){
      command += " --terms '" + terms.to_s + "'"
    }
    command += " > /dev/null 2>&1"
    cy.exec(command)
  }

}
export default SearchLogs;