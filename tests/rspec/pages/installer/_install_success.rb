module Installer
  class Success < SitePrism::Section
    element :success_message, 'form.settings div.success'
    element :success_header, 'form.settings div.success h3'
    element :login_button, 'form.settings a.btn'
  end
end
