module Installer
  class Success < SitePrism::Section
    element :success_message, 'div.success h1'
    element :updater_msg, 'div.updater-msg'
    element :login_button, 'p.msg-choices a'
  end
end
