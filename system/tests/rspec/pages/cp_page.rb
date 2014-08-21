class ControlPanelPage < SitePrism::Page

	section :main_menu, MenuSection, 'section.menu-wrap'
	element :submit_button, '.form-ctrls input.btn'
	element :submit_button_disabled, '.form-ctrls input.btn.disable'
	element :fieldset_errors, 'fieldset.invalid'
	element :settings_btn, 'b.ico.settings'
	element :error_message, 'em.ee-form-error-message'

	# Tables
	element :select_all, 'th.check-ctrl input'
	elements :sort_links, 'table a.sort'

	def open_dev_menu
		main_menu.dev_menu.click
	end

	def submit
		submit_button.click
	end

	def submit_enabled?
		submit_button.value != 'Fix Errors, Please' &&
		submit_button[:disabled] != true &&
		self.has_submit_button_disabled? == false
	end

	# Waits until the error message is gone before proceeding;
	# if we just check for invisible but it's already gone,
	# Capybara will complain, so we check for its existance first
	def wait_for_no_error
		if self.has_error_message?
			self.wait_until_error_message_invisible
		end
	end
end