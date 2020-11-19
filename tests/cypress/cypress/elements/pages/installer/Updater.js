import ControlPanel from '../ControlPanel'

class Updater extends ControlPanel {
  constructor() {
        super()
    this.url =  '/admin.php';

    this.selectors = Object.assign(this.selectors, {
        "login_header" : 'h1',
        "header" : 'h3',
        "updater_steps" : 'ul.updater-steps',
        "error" : 'div.issue, div.app-notice---error',
        "submit" : 'form input[type=submit]',
        "success_actions" : 'p.msg-choices a',
        "inline_errors" : '.fieldset-invalid em'
    })
  }


    // Find an error message in the inline errors array
    // cypress should have a added in method for this similar to Authvisit.
    // @param [String/Regex] error_message Either a string or regular expression to search for
    // @return [Boolean] true if found, false if not found
    /*has_inline_error(error_message){
        { |element| self.inline_errors
            if(error_message == element.text){
                return true;
            }
        }
        return false;
    }*/

}

export default Updater;
