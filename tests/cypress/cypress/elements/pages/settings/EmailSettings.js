import ControlPanel from '../ControlPanel'

class EmailSettings extends ControlPanel {
  constructor() {
      super()

      this.elements({
        'webmaster_email': 'input[type!=hidden][name=webmaster_email]',
        'webmaster_name': 'input[type!=hidden][name=webmaster_name]',
        'email_charset': 'input[type!=hidden][name=email_charset]',
        'mail_protocol': 'input[type!=hidden][name=mail_protocol]',
        'email_newline': 'input[type!=hidden][name=email_newline]',
        'smtp_server': 'input[type!=hidden][name=smtp_server]',
        'smtp_port': 'input[type!=hidden][name=smtp_port]',
        'smtp_username': 'input[type!=hidden][name=smtp_username]',
        'smtp_password': 'input[type!=hidden][name=smtp_password]',
        'email_smtp_crypto': 'input[type!=hidden][name=email_smtp_crypto]',
        'mail_format': 'input[type!=hidden][name=mail_format]',
        'word_wrap': 'input[name=word_wrap]',//: :visible => false
        'word_wrap_toggle': '[data-toggle-for=word_wrap]'
      })
  }

  load() {
    this.get('settings_btn').click()
    this.get('wrap').find('div.sidebar a:contains("Outgoing Email")').click()
  }
}
export default EmailSettings;