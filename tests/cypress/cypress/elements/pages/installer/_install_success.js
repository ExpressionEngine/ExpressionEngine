import ControlPanel from '../ControlPanel'

class Success extends ControlPanel {
    constructor() {
		super()

		this.all_there = {

	    	"success_message" : 'div.success h1',
    		"updater_msg" : 'div.updater-msg',
    		"login_button" : 'p.msg-choices a'
		}
	    this.selectors = Object.assign(this.selectors, this.all_there)
    }
}
export default Success;