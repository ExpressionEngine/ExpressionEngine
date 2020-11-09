import ControlPanel from '../ControlPanel'

class EmailLog extends ControlPanel {
  constructor() {
    super()

    this.selectors = Object.assign(this.selectors, {
              "heading" : 'div.w-12 div.box div.tbl-ctrls h1',
              "username_filter": 'div.filters a[data-filter-label^="username"]',
              "username_filter_menu": 'div.filters a[data-filter-label^="username"] + div.sub-menu ul',
              "username_manual_filter": 'input[name="filter_by_username"]',
              "date_filter": 'div.filters a[data-filter-label^="date"]',
              "date_filter_menu": 'div.filters a[data-filter-label^="date"] + div.sub-menu ul',
              "date_manual_filter": 'input[name="filter_by_date"]',
              "perpage_filter": 'div.filters a[data-filter-label^="show"]',
              "perpage_filter_menu": 'div.filters a[data-filter-label^="show"] + div.sub-menu ul',
              "perpage_manual_filter": 'input[name="perpage"]',
              "no_results": 'p.no-results',
              "remove_all": 'button.btn.action',
              "items": 'section.item-wrap div.item'
        }) 
    
    this.urlMatcher = 'admin.php?/cp/logs/email';

    
    this.count= 250;
    this.member_id= null;
    this.member_name= null;
    this.ip_address= null;
    this.timestamp_min= null;
    this.timestamp_max= null;
    this.recipient= null;
    this.recipient_name= null;
    this.subject= null;
    this.message= null;

  }



  runner(){
  	let command = "cd fixtures && php emailLog.php"

  	if (this.count >0){
      command += " --count " + count.toString()
  	}
    

    if (this.member_id){
      command += " --member-id " + member_id.toString()
    }
    

    if (this.member_name){
      command += " --member-name '" + member_name.toString() + "'"
    }

    if (this.ip_address){
      command += " --ip-address '" + ip_address.toString() + "'"
    }
    

    if (this.timestamp_min){
      command += " --timestamp-min " + timestamp_min.toString()
    }
    

    if (this.timestamp_max){
      command += " --timestamp-max " + timestamp_max.toString()
    }
    

    if (this.recipient){
      command += " --recipient '" + recipient.toString() + "'"
    }
    

    if (this.recipient_name){
      command += " --recipient-name '" + recipient_name.toString() + "'"
    }
    

    if (this.subject){
      command += " --subject '" + subject.toString() + "'"
    }
    

    if (this.message){
      command += " --message '" + message.toString() + "'"
    }
    

    command += " > /dev/null 2>&1"
    cy.exec(command)
  }

}
export default EmailLog;