import ControlPanel from '../ControlPanel'

class Form extends ControlPanel {
    constructor() {
        super()

		this.all_there = {
			"db_hostname": 'input[type!=hidden][name=db_hostname]',
			"db_name": 'input[type!=hidden][name=db_name]',
			"db_username" : 'input[type!=hidden][name=db_username]',
			"db_password" : 'input[type!=hidden][name=db_password]',
			"db_prefix" : 'input[type!=hidden][name=db_prefix]',
			"install_default_theme" : 'input[type!=hidden][name=install_default_theme]',
			"username" : 'input[type!=hidden][name=username]',
			"email_address" : 'input[type!=hidden][name=email_address]',
			"password" : 'input[type!=hidden][name=password]',
			"license_agreement" : 'input[type!=hidden][name=license_agreement]',
			"install_submit" : 'form input[type=submit]'
		}
	    this.selectors = Object.assign(this.selectors, this.all_there)

	}
}
export default Form;