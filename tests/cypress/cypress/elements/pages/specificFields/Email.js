import ControlPanel from '../ControlPanel'

class Email extends ControlPanel {
    constructor() {
            super()
            
            this.elements({
               //Constants for all
                "Save": 'button[type="submit"][value="save"]',
                "Name" : 'input[type="text"][name = "field_label"]',
                "Instructions" : 'textarea[name = "field_instructions"]',
                "Required" : 'button[data-toggle-for = "field_required"]',
                "Search" : 'button[data-toggle-for = "field_search"]',
                "Hidden" : 'button[data-toggle-for = "field_is_hidden"]'


            })
        }
}
export default Email;