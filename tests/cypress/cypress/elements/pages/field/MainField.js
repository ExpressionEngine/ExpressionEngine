import ControlPanel from '../ControlPanel'

class MainField extends ControlPanel {
    constructor() {
            super()
            
            
            this.elements({
                "NewGroup" : "a[href = 'admin.php?/cp/fields/groups/create']"
                
            })
        }

   
       
}
export default MainField;