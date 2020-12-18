import ControlPanel from '../ControlPanel'

class ColorPicker extends ControlPanel {
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

                //Field Options Allowed Colors
                "AnyColor" : 'input[name="allowed_colors"][value="any"]',
                "Swatches" : 'input[name="allowed_colors"][value="swatches"]',
                "DefaultColor" : 'input[class="colorpicker__input"]',
               
               //Swatches
               "Values" : 'input[name="populate_swatches"][value="v"]',
               "Manual" : 'input[name="populate_swatches"][value="m"]',

               "AddSwatch" : 'a[rel="add_row"]',
                    "SwatchInput" : 'input[name="value_swatches[rows][new_row_2][color]"]'
                    //for additional Inputs the 2 would just increment
                
            })
        }
       
}
export default ColorPicker;