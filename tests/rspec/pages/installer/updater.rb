module Installer
  class Updater < SitePrism::Page
    set_url '/system/index.php'

    element :header, 'h1'
    element :req_title, 'h1 .req-title'
    element :error, 'div.issue'

    element :submit, 'form input[type=submit]'
    element :login, 'form input[name=login]'
    element :download, 'form input[name=download]'

    elements :inline_errors, '.setting-field em'

    # Find an error message in the inline errors array
    #
    # @param [String/Regex] error_message Either a string or regular expression to search for
    # @return [Boolean] true if found, false if not found
    def has_inline_error(error_message)
      self.inline_errors.each do |element|
        return true if element.text.match(error_message)
      end
      false
    end
  end
end
