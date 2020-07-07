import ControlPanel from '../ControlPanel'
import './logs.js'
class DeveloperLog extends ControlPanel {
  constructor() {
    super()

    this.urlMatcher = /logs\/developer/

   }


    generate_data(
	    count = 250,
	    timestamp_min = null,
	    timestamp_max = null,
	    description = null
    )

{
	let command = "cd fixtures && php developerLog.php"

	if (count){
      command += " --count " + count.toString()
	
    }

    if (timestamp_min){
      command += " --timestamp-min " + timestamp_min.toString()
    }
    

    if (timestamp_max){
      command += " --timestamp-max " + timestamp_max.toString()
    }
    

    if (description){
      command += " --description '" + description.toString() + "'"
    }
    

    command += " > /dev/null 2>&1"
    cy.exec(command)
}

