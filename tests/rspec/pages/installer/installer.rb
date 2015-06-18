module Installer
  class Base < SitePrism::Page
    set_url '/system/index.php'

    section :install_form, Installer::Form, 'section.wrap'
    section :install_success, Installer::Success, 'section.wrap'

    element :header, 'h1'
    element :req_title, 'h1 .req-title'
  end
end
