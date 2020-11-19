import ControlPanel from '../ControlPanel'

class Checkbox extends ControlPanel {
    constructor() {
            super()
            
            this.elements({
               //Constants for all
                "Save": 'button[type="submit"][value="save"]',
                "Name" : 'input[type="text"][name = "field_label"]',
                "Instructions" : 'textarea[name = "field_instructions"]',
                "Required" : 'button[data-toggle-for = "field_required"]',
                "Search" : 'button[data-toggle-for = "field_search"]',
                "Hidden" : 'button[data-toggle-for = "field_is_hidden"]',

                //Field Options
                "TextFormating" : 'div[class="checkbox-label__text"]', 
                //then use .contains() in spec to select option.
                // This selector also works for Checkbox options with the .contains idea
                
                //Checkbox options
                "NewRow" : 'a[rel="add_row"]'
            })
        }
       
}
export default Checkbox;