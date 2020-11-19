import ControlPanel from '../ControlPanel'

class Duration extends ControlPanel {
    constructor() {
            super()
            
            this.elements({
               //Constants for all Date only has these
                "Save": 'button[type="submit"][value="save"]',
                "Name" : 'input[type="text"][name = "field_label"]',
                "Instructions" : 'textarea[name = "field_instructions"]',
                "Required" : 'button[data-toggle-for = "field_required"]',
                "Search" : 'button[data-toggle-for = "field_search"]',
                "Hidden" : 'button[data-toggle-for = "field_is_hidden"]',

                //Field Options
                "FileTypes" : 'div[class="field-inputs"]', //then do .find('label').contains(All or image)

                //Directories
                "All" : 'input[name="allowed_directories"][value="all"]',
                "OtherDirectories" : 'div[class= "checkbox-label__text"]',//.contains('nameofDir')

                //Channel Settings
                "Exist" : 'button[data-toggle-for= "show_existing"]',
                "Limit" : 'input[name="num_existing"]'
            })
        }
}
export default Duration;