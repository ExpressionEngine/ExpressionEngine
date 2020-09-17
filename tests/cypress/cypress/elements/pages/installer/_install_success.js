import ControlPanel from '../ControlPanel'

class Success extends ControlPanel {
    constructor() {
        super()
	    this.selectors = Object.assign(this.selectors, {

	    	"success_message" : 'div.success h1',
    		"updater_msg" : 'div.updater-msg',
    		"login_button" : 'p.msg-choices a'
    	)}
    }
}
export default Success;