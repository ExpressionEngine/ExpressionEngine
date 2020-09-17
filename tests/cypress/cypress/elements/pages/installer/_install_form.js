import ControlPanel from '../ControlPanel'

class Form extends ControlPanel {
    constructor() {
        super()

	    this.selectors = Object.assign(this.selectors, {
	    	    "db_hostname": 'input[name=db_hostname]',
			    "db_name": 'input[name=db_name]',
			    "db_username" : 'input[name=db_username]',
			    "db_password" : 'input[name=db_password]',
			    "db_prefix" : 'input[name=db_prefix]',
			    "install_default_theme" : 'input[name=install_default_theme]',
			    "username" : 'input[name=username]',
			    "email_address" : 'input[name=email_address]',
			    "password" : 'input[name=password]',
			    "license_agreement" : 'input[name=license_agreement]',
			    "install_submit" : 'form input[type=submit]'
	    )}

	}
}
export default Form;