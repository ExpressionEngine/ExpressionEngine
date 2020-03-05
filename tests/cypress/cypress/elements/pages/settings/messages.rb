class MessagingSettings < ControlPanelPage

  element :prv_msg_max_chars, 'input[name=prv_msg_max_chars]'
  elements :prv_msg_html_format, 'input[name=prv_msg_html_format]'
  element :prv_msg_auto_links, 'input[name=prv_msg_auto_links]', :visible => false
  element :prv_msg_auto_links_toggle, 'a[data-toggle-for=prv_msg_auto_links]'
  element :prv_msg_upload_url, 'input[name=prv_msg_upload_url]'
  element :prv_msg_upload_path, 'input[name=prv_msg_upload_path]'
  element :prv_msg_max_attachments, 'input[name=prv_msg_max_attachments]'
  element :prv_msg_attach_maxsize, 'input[name=prv_msg_attach_maxsize]'
  element :prv_msg_attach_total, 'input[name=prv_msg_attach_total]'

  def load
    settings_btn.click
    within 'div.sidebar' do
      click_link 'Messages'
    end
  end
end
