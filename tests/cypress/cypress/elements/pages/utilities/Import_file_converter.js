import ControlPanel from '../ControlPanel'

class ImportConverter extends ControlPanel {
    constructor() {
        super()

        this.selectors = Object.assign(this.selectors, {



        	  "file_location" :'input[type!=hidden][name=member_file]',
			  "delimiter": 'div[class="checkbox-label__text"]',
			  "delimiter_special" :'input[type!=hidden][name=delimiter_special]',
			  "enclosing_char": 'input[type!=hidden][name=enclosure]',

              "send_it" : 'input[value="Convert File"]',



			  // Assign fields page
			  "field1" :'select[name=field_0]',
			  "field2" :'select[name=field_1]',
			  "field3" :'select[name=field_2]',
			  "field4" :'select[name=field_3]',
			  // XML Code page
			  "xml_code" :'textarea.template-edit',

              "send_it_2" : 'input[value="Assign Fields"]'

        })
    }

    load(){
    	 this.open_dev_menu()
    	 cy.get('body > .dropdown').find('a:contains("Utilities")').click()
    	 this.get('wrap').find('a:contains("File Converter")').click()

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
export default ImportConverter;

