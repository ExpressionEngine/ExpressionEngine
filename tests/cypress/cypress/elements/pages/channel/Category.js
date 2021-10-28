import ControlPanel from '../ControlPanel'

class Category extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/categories';

        this.selectors = Object.assign(this.selectors, {
            
        })
    }


}
export default Category;
