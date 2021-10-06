import ControlPanel from '../ControlPanel'

class Communicate extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/utilities/communicate';

        this.selectors = Object.assign(this.selectors, {
        	  "heading" : 'div.w-12 form h1',
			  "subject" : 'input[type!=hidden][name="subject"]',
			  "body"  : 'textarea[name="message"]',
			  "plaintext_alt" : 'textarea[name="plaintext_alt"]',
			  "mailtype" : 'select[name="mailtype"]',
			  "wordwrap" : 'input[type!=hidden][name="wordwrap"]',
			  "from_email" : 'input[type!=hidden][name="from"]',
			  "attachment" : 'input[type!=hidden][name="attachment"]',
			  "recipient" : 'input[type!=hidden][name="recipient"]',
			  "cc" : 'input[type!=hidden][name="cc"]',
			  "bcc" : 'input[type!=hidden][name="bcc"]',
			  "member_groups" : 'div[data-input-value="member_groups"] input',
			  "submit_button" : '.tab-bar__right-buttons .form-btns [type="submit"]'
        })

    }

    load(){
    	this.open_dev_menu()
    	cy.get('body > .dropdown').find('a:contains("Utilities")').click()
    }

}
export default Communicate;
