import ControlPanel from '../ControlPanel'

class CreateField extends ControlPanel {
    constructor() {
            super()
            
            this.url = 'admin.php?/cp/fields/create';
            this.elements({
                "Type" : '#fieldset-field_type',
                "Type_Options" : "div[class= 'select__dropdown-item']", 
                "Save": 'button[type="submit"][value="save"]',

                "Name" : 'input[type="text"][name = "field_label"]',
                "Instructions" : 'textarea[name = "field_instructions"]',

                "Required" : 'button[data-toggle-for = "field_required"]',
                "Search" : 'button[data-toggle-for = "field_search"]',
                "Hidden" : 'button[data-toggle-for = "field_is_hidden"]',

                //Field Options
                "MinRows" : 'input[name = "grid_min_rows"]',
                "MaxRows" : 'input[name = "grid_max_rows"]',
                "Reorder" : 'button[data-toggle-for = "allow_reorder"]',

                //Grid Fields
                "NewCol" : 'a[rel= "add_new"]',
                "ColType" : 'label[class= "select__button-label act"]',

                "GridName" : 'input[name = "grid[cols][new_0][col_label]"]',
                "GridInstruction" : 'textarea[name = "grid[cols][new_0][col_instructions]"]',
                "GridRequired" : 'button[data-toggle-for = "grid[cols][new_0][col_required]"]'
            })
        }
       
}
export default CreateField;