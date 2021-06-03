import ControlPanel from '../ControlPanel'

class MemberImport extends ControlPanel {
    constructor() {
        super()
       // this.url = no url given in coresponding rb file;

        this.selectors = Object.assign(this.selectors, {

        	"general_radio" : 'div[class="checkbox-label__text"]',
            "send_it" : '[value="Import Members"]',

		  "file_location": 'input[type!=hidden][name=xml_file]',
		  "member_group": 'input[type!=hidden][name=group_id]',
		  "language": 'input[type!=hidden][name=language]',
		  "tz_country": 'select[name=tz_country]',
		  "timezone": 'select[name=timezones]',
		  "date_format": 'input[type!=hidden][name=date_format]',
		  "time_format": 'input[type!=hidden][name=time_format]',
		  "auto_custom_field": 'input[name=auto_custom_field]',
		  "auto_custom_field_toggle": '[data-toggle-for=auto_custom_field]',
		  "include_seconds": 'input[name=include_seconds]',
		  "include_seconds_toggle": '[data-toggle-for=include_seconds]',
		  "table": 'table',
		  "options": 'table tr td:first-child',
		  "values": 'table tr td:nth-child(2)',
		  // Custom field creation
		  "select_all": 'input[type!=hidden][name=select_all]',
		  "custom_field_1": 'input[type!=hidden][name="create_ids[0]"]',
		  "custom_field_2": 'input[type!=hidden][name="create_ids[1]"]',
		  "custom_field_1_name": 'input[type!=hidden][name="m_field_name[0]"]',
		  "custom_field_2_name": 'input[type!=hidden][name="m_field_name[1]"]'
        })
    }

    load(){
    	this.open_dev_menu()
    	cy.get('body > .dropdown').find('a:contains("Utilities")').click()
    	this.get('wrap').find('a:contains("Member Import")').click()
    }



    submit(fileName, fileType, selector){
        cy.get(selector).then(subject => {
                cy.fixture(fileName, 'base64')
                .then(Cypress.Blob.base64StringToBlob)
                .then(blob => {
                    const el = subject[0]
                    const testFile = new File([blob], fileName, { type: fileType })
                    const dataTransfer = new DataTransfer()
                    dataTransfer.items.add(testFile)
                    el.files = dataTransfer.files
                    console.log(el.files)
              })
        })
    }


}

export default MemberImport;
