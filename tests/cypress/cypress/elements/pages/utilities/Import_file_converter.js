import ControlPanel from '../ControlPanel'

class ImportConverter extends ControlPanel {
    constructor() {
        super()
        // this.url = ; no url given in rb

        this.selectors = Object.assign(this.selectors, {

        	  "file_location" :'input[name=member_file]',
			  "delimiter": 'label',
			  "delimiter_special" :'input[name=delimiter_special]',
			  "enclosing_char": 'input[name=enclosure]',
			  // Assign fields page
			  "field1" :'select[name=field_0]',
			  "field2" :'select[name=field_1]',
			  "field3" :'select[name=field_2]',
			  "field4" :'select[name=field_3]',
              "send_it" : 'input[value="Convert File"]',
			  // XML Code page
			  "xml_code" :'textarea.template-edit'

        })
    }

    load(){
    	 this.open_dev_menu()
    	 this.get('main_menu').find('a:contains("Utilities")').click()
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