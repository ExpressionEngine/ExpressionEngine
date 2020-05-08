import ControlPanel from '../ControlPanel'

class CommunicateSent extends ControlPanel {
  constructor() {
    super()
    this.urlMatcher = /utilities\/communicate\/sent/

    this.elements({

      'heading': 'div.w-12 form h1',

      'phrase_search': 'input[name=search]',
      'search_submit_button': 'form fieldset.tbl-search input.submit',

      'email_table': 'div.box div.tbl-ctrls form div.tbl-wrap table',
      'no_results': 'div.box div.tbl-ctrls form div.tbl-wrap table tr.no-results',
      'subject_header': 'div.box div.tbl-ctrls form div.tbl-wrap table tr th:first-child',
      'date_header': 'div.box div.tbl-ctrls form div.tbl-wrap table tr th:nth-child(2)',
      'total_sent_header': 'div.box div.tbl-ctrls form div.tbl-wrap table tr th:nth-child(3)',
      'rows': 'div.box div.tbl-ctrls form div.tbl-wrap table tr',
      'subjects': 'div.box div.tbl-ctrls form div.tbl-wrap table tr td:first-child',
      'dates': 'div.box div.tbl-ctrls form div.tbl-wrap table tr td:nth-child(2)',
      'total_sents': 'div.box div.tbl-ctrls form div.tbl-wrap table tr td:nth-child(3)',

      'bulk_action': 'form fieldset.bulk-action-bar select[name="bulk_action"]',
      'action_submit_button': 'form fieldset.bulk-action-bar button'
    })

  }

  generate_data(
    count= 250,
    timestamp= null,
    timestamp_min= null,
    timestamp_max= null,
    from_name= null,
    from_email= null,
    recipient= null,
    cc= null,
    bcc= null,
    subject= null,
    message= null,
    total_sent= null
    )

  {
      let command = "cd fixtures && php emailCache.php"

      if (count) {
        command += " --count " + count.toString()
      }

      if (timestamp) {
        command += " --timestamp " + timestamp.toString()
      }

      if (timestamp_min) {
        command += " --timestamp-min " + timestamp_min.toString()
      }

      if (timestamp_max) {
        command += " --timestamp-max " + timestamp_max.toString()
      }

      if (from_name) {
        command += " --from-name '" + from_name.toString() + "'"
      }

      if (from_email) {
        command += " --from-email '" + from_email.toString() + "'"
      }

      if (recipient) {
        command += " --recipient '" + recipient.toString() + "'"
      }

      if (cc) {
        command += " --cc '" + cc.toString() + "'"
      }

      if (bcc) {
        command += " --bcc '" + bcc.toString() + "'"
      }

      if (subject) {
        command += " --subject '" + subject.toString() + "'"
      }

      if (message) {
        command += " --message '" + message.toString() + "'"
      }

      if (total_sent) {
        command += " --total-sent '" + total_sent.toString() + "'"
      }

      command += " > /dev/null 2>&1"

      cy.exec(command)
  }

  load() {
    this.open_dev_menu()
    this.get('main_menu').find('a:contains("Utilities")').click()
    this.get('wrap').find('a:contains("Sent")').click()
  }
}
export default CommunicateSent;