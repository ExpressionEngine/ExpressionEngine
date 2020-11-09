import ControlPanel from '../ControlPanel'

class MessagingSettings extends ControlPanel {
  constructor() {
      super()

      this.elements({
        'prv_msg_max_chars': 'input[name=prv_msg_max_chars]',
        'prv_msg_html_format': 'input[name=prv_msg_html_format]',
        'prv_msg_auto_links': 'input[name=prv_msg_auto_links]',//visible => false
        'prv_msg_auto_links_toggle': 'a[data-toggle-for=prv_msg_auto_links]',
        'prv_msg_upload_url': 'input[name=prv_msg_upload_url]',
        'prv_msg_upload_path': 'input[name=prv_msg_upload_path]',
        'prv_msg_max_attachments': 'input[name=prv_msg_max_attachments]',
        'prv_msg_attach_maxsize': 'input[name=prv_msg_attach_maxsize]',
        'prv_msg_attach_total': 'input[name=prv_msg_attach_total]'

      })
  }

  load() {
    this.get('settings_btn').click()
    this.get('wrap').find('div.sidebar a:contains("Messages")').click()
  }
}
export default MessagingSettings;