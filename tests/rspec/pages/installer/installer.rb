class Installer < SitePrism::Page
  set_url '/system/index.php'
  element :submit, 'form input[type=submit]'
end
