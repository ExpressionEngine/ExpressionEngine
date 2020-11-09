import ControlPanel from '../ControlPanel'

class Communicate extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/utilities/communicate';

        this.selectors = Object.assign(this.selectors, {
        	  "heading" : 'div.w-12 form h1',
			  "subject" : 'input[name="subject"]',
			  "body"  : 'textarea[name="message"]',
			  "plaintext_alt" : 'textarea[name="plaintext_alt"]',
			  "mailtype" : 'select[name="mailtype"]',
			  "wordwrap" : 'input[name="wordwrap"]',
			  "from_email" : 'input[name="from"]',
			  "attachment" : 'input[name="attachment"]',
			  "recipient" : 'input[name="recipient"]',
			  "cc" : 'input[name="cc"]',
			  "bcc" : 'input[name="bcc"]',
			  "member_groups" : 'div[data-input-value="member_groups"] input',
			  "submit_button" : 'div.form-btns.form-btns-top input[type="submit"]'
        })

    }

    load(){
    	this.open_dev_menu()
    	this.get('main_menu').find('a:contains("Utilities")').click()
    }

}
export default Communicate;