import ControlPanel from '../ControlPanel'

class EmailSettings extends ControlPanel {
  constructor() {
      super()

      this.elements({
        'webmaster_email': 'input[name=webmaster_email]',
        'webmaster_name': 'input[name=webmaster_name]',
        'email_charset': 'input[name=email_charset]',
        'mail_protocol': 'input[name=mail_protocol]',
        'email_newline': 'input[name=email_newline]',
        'smtp_server': 'input[name=smtp_server]',
        'smtp_port': 'input[name=smtp_port]',
        'smtp_username': 'input[name=smtp_username]',
        'smtp_password': 'input[name=smtp_password]',
        'email_smtp_crypto': 'input[name=email_smtp_crypto]',
        'mail_format': 'input[name=mail_format]',
        'word_wrap': 'input[name=word_wrap]',//: :visible => false
        'word_wrap_toggle': 'a[data-toggle-for=word_wrap]'
      })
  }

  load() {
    this.get('settings_btn').click()
    this.get('wrap').find('div.sidebar a:contains("Outgoing Email")').click()
  }
}
export default EmailSettings;