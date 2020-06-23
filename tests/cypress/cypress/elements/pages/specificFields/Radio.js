import ControlPanel from '../ControlPanel'

class Radio extends ControlPanel {
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

                //Feild Text Formating
                "Auto" : 'input[value="br"]',
                "None" : 'input[value="none"]',
                "X" : 'input[value="xhtml"]',

                //Multi Select
                "Value/Label" : 'input[value="v"]',
                "Manual" : 'input[value="n"]',
                "Other Field" : 'input[value="y"]'
            })
        }
}
export default Radio;