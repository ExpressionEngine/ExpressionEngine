import Logs from './logs'

class EmailLog extends Logs {
  constructor() {
    super()

    this.url = 'admin.php?/cp/logs/email';

  }

  generate_data(
    count= 250,
    member_id= null,
    member_name= null,
    ip_address= null,
    timestamp_min= null,
    timestamp_max= null,
    recipient= null,
    recipient_name= null,
    subject= null,
    message= null
    )

  {
  	let command = "cd support/fixtures && php emailLog.php"

  	if (count){
      command += " --count " + count.toString()
  	}


    if (member_id){
      command += " --member-id " + member_id.toString()
    }


    if (member_name){
      command += " --member-name '" + member_name.toString() + "'"
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


    if (recipient){
      command += " --recipient '" + recipient.toString() + "'"
    }


    if (recipient_name){
      command += " --recipient-name '" + recipient_name.toString() + "'"
    }


    if (subject){
      command += " --subject '" + subject.toString() + "'"
    }


    if (message){
      command += " --message '" + message.toString() + "'"
    }


    command += " > /dev/null 2>&1"
    cy.exec(command)
  }

}
export default EmailLog;