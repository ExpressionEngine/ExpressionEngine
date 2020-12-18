import ControlPanel from '../ControlPanel'

class CreateGroup extends ControlPanel {
    constructor() {
            super()
            
            
            this.elements({
                "GroupName" : "input[name = 'group_name']",
                "Options" : "div[class = 'checkbox-label__text']",
                "Save": 'button[type="submit"][value="save"]'
                
            })
        }

   
       
}
export default CreateGroup;