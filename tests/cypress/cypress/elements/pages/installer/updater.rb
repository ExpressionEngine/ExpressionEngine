module Installer
  class Updater < SitePrism::Page
    set_url '/admin.php'

    element :header, 'h1'
    element :updater_steps, 'ul.updater-steps'
    element :error, 'div.issue, div.app-notice---error'

    element :submit, 'form input[type=submit]'
    elements :success_actions, 'p.msg-choices a'

    elements :inline_errors, '.fieldset-invalid em'

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
