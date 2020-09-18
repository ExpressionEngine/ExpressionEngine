// import ControlPanel from '../ControlPanel'

// class CommunicateSent extends ControlPanel {
//   constructor() {
//     super()
//     this.urlMatcher = /utilities\/communicate\/sent/

//     this.elements({

//       'heading': 'div.w-12 form h1',

//       'phrase_search': 'input[type!=hidden][name=search]',
//       'search_submit_button': 'form fieldset.tbl-search input.submit',

//       'email_table': 'div.box div.tbl-ctrls form div.tbl-wrap table',
//       'no_results': 'div.box div.tbl-ctrls form div.tbl-wrap table tr.no-results',
//       'subject_header': 'div.box div.tbl-ctrls form div.tbl-wrap table tr th:first-child',
//       'date_header': 'div.box div.tbl-ctrls form div.tbl-wrap table tr th:nth-child(2)',
//       'total_sent_header': 'div.box div.tbl-ctrls form div.tbl-wrap table tr th:nth-child(3)',
//       'rows': 'div.box div.tbl-ctrls form div.tbl-wrap table tr',
//       'subjects': 'div.box div.tbl-ctrls form div.tbl-wrap table tr td:first-child',
//       'dates': 'div.box div.tbl-ctrls form div.tbl-wrap table tr td:nth-child(2)',
//       'total_sents': 'div.box div.tbl-ctrls form div.tbl-wrap table tr td:nth-child(3)',
//     })

//   }

//   generate_data(
//     count= 250,
//     timestamp= null,
//     timestamp_min= null,
//     timestamp_max= null,
//     from_name= null,
//     from_email= null,
//     recipient= null,
//     cc= null,
//     bcc= null,
//     subject= null,
//     message= null,
//     total_sent= null
//     )

//   {
//       let command = "cd fixtures && php emailCache.php"

//       if (count) {
//         command += " --count " + count.toString()
//       }

//       if (timestamp) {
//         command += " --timestamp " + timestamp.toString()
//       }

//       if (timestamp_min) {
//         command += " --timestamp-min " + timestamp_min.toString()
//       }

//       if (timestamp_max) {
//         command += " --timestamp-max " + timestamp_max.toString()
//       }

//       if (from_name) {
//         command += " --from-name '" + from_name.toString() + "'"
//       }

//       if (from_email) {
//         command += " --from-email '" + from_email.toString() + "'"
//       }

//       if (recipient) {
//         command += " --recipient '" + recipient.toString() + "'"
//       }

//       if (cc) {
//         command += " --cc '" + cc.toString() + "'"
//       }

//       if (bcc) {
//         command += " --bcc '" + bcc.toString() + "'"
//       }

//       if (subject) {
//         command += " --subject '" + subject.toString() + "'"
//       }

//       if (message) {
//         command += " --message '" + message.toString() + "'"
//       }

//       if (total_sent) {
//         command += " --total-sent '" + total_sent.toString() + "'"
//       }

//       command += " > /dev/null 2>&1"

//       cy.exec(command)
//   }

//   load() {
//     this.open_dev_menu()
//     cy.get('body > .dropdown').find('a:contains("Utilities")').click()
//     this.get('wrap').find('a:contains("Sent")').click()
//   }
// }
// export default CommunicateSent;

import ControlPanel from '../ControlPanel'

class CommunicateSent extends ControlPanel {
  constructor() {
    super()
    //this.urlMatcher = /utilities\/communicate\/sent/
    this.url = 'admin.php?/cp/utilities/communicate';

    this.count= 250;
    this.timestamp= null;
    this.timestamp_min= null;
    this.timestamp_max= null;
    this.from_name= null;
    this.from_email= null;
    this.recipient= null;
    this.cc= null;
    this.bcc= null;
    this.subject= null;
    this.message= null;
    this.total_sent= null;

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

      'bulk_action': 'form fieldset.tbl-bulk-act select[name="bulk_action"]',
      'action_submit_button': 'form fieldset.tbl-bulk-act button.submit'
    })

  }

  SentSorter(){

    cy.get(':nth-child(3) > .sort').click({force: true})
    let a = 0;
    for(a;a<18;a++){
      let b = a + 1;
      this.get('total_sents').eq(a).invoke('text').then((text1) => {
        this.get('total_sents').eq(b).invoke('text').should((text2) => {
          expect(parseInt(text1)).to.equal(parseInt(text2));
        })
      })
    }


    cy.auth();
    this.load();
    cy.get(':nth-child(3) > .sort').click({force: true})

    let c = 0;
    for(c;c<18;c++){
      let d = c + 1;
      this.get('total_sents').eq(c).invoke('text').then((text1) => {
        this.get('total_sents').eq(d).invoke('text').should((text2) => {

          if(parseInt(text1) >= parseInt(text1) ){
            cy.expect(42).to.equal(42)
          }else{
             cy.expect(42).to.equal(41) //have cypress throw error
          }

        })
      })
    }



  }

  SubjectSorter(){
    let forward = ['A', 'B','C','D','E', 'F','G','H','I', 'J','K','L','M', 'N','O','P','Q', 'R','S','T','U', 'V','W','X','Y','Z'];
    let i = 0;
    for(i; i < forward.length; i++){
     this.subject = forward[i];
     this.count = 1;
     //this.runner();
    }

     let j = 0;
     for(j; j<20;j++){
      this.get('subjects').eq(j).contains(forward[j])
     }

     cy.auth();
     this.load();

     cy.get('.highlight > .sort').click({force: true})
     let back = forward.reverse();
     let k = 0;
     for(k; k<20;k++){

      this.get('subjects').eq(k).contains(back[k])
     }


  }


  runner(){
      let command = "cd support/fixtures && php emailCache.php"

      if (this.count) {
        command += " --count " + this.count.toString()
      }

      if (this.timestamp) {
        command += " --timestamp " + this.timestamp.toString()
      }

      if (this.timestamp_min) {
        command += " --timestamp-min " + this.timestamp_min.toString()
      }

      if (this.timestamp_max) {
        command += " --timestamp-max " + this.timestamp_max.toString()
      }

      if (this.from_name) {
        command += " --from-name '" + this.from_name.toString() + "'"
      }

      if (this.from_email) {
        command += " --from-email '" + this.from_email.toString() + "'"
      }

      if (this.recipient) {
        command += " --recipient '" + this.recipient.toString() + "'"
      }

      if (this.cc) {
        command += " --cc '" + this.cc.toString() + "'"
      }

      if (this.bcc) {
        command += " --bcc '" + this.bcc.toString() + "'"
      }

      if (this.subject) {
        command += " --subject '" + this.subject.toString() + "'"
      }

      if (this.message) {
        command += " --message '" + this.message.toString() + "'"
      }

      if (this.total_sent) {
        command += " --total-sent '" + this.total_sent.toString() + "'"
      }

      //command += " > /dev/null 2>&1"

      cy.exec(command)
  }

  load() {
    this.open_dev_menu()
    cy.get('body > .dropdown').find('a:contains("Utilities")').click()
    this.get('wrap').find('a:contains("Sent")').click()
    cy.get('input[name="filter_by_keyword"]').should('exist')
  }
}
export default CommunicateSent;