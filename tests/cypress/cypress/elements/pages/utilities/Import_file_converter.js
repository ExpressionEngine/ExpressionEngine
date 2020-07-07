import ControlPanel from '../ControlPanel'

class ImportConverter extends ControlPanel {
    constructor() {
        super()
        // this.url = ; no url given in rb

        this.selectors = Object.assign(this.selectors, {

        	  "file_location" :'input[type!=hidden][name=member_file]',
			  "delimiter": 'input[type!=hidden][name=delimiter]',
			  "delimiter_special" :'input[type!=hidden][name=delimiter_special]',
			  "enclosing_char": 'input[type!=hidden][name=enclosure]',
			  // Assign fields page
			  "field1" :'select[name=field_0]',
			  "field2" :'select[name=field_1]',
			  "field3" :'select[name=field_2]',
			  "field4" :'select[name=field_3]',
			  // XML Code page
			  "xml_code" :'textarea.template-edit'

        })
    }

    load(){
    	 this.open_dev_menu()
    	 this.get('main_menu').find('a:contains("Utilities")').click()
    	 this.get('wrap').find('a:contains("File Converter")').click()

    }


}
export default ImportConverter;