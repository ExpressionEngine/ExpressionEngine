module Installer
  class Form < SitePrism::Section
    element :db_hostname, 'input[name=db_hostname]'
    element :db_name, 'input[name=db_name]'
    element :db_username, 'input[name=db_username]'
    element :db_password, 'input[name=db_password]'
    element :db_prefix, 'input[name=db_prefix]'

    # TODO: Enable this when we add the default theme back
    # elements :install_default_theme, 'input[name=install_default_theme]'

    element :username, 'input[name=username]'
    element :email_address, 'input[name=email_address]'
    element :password, 'input[name=password]'

    element :install_submit, 'form input[type=submit]'
  end
end
