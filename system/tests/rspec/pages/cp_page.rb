class ControlPanelPage < SitePrism::Page

	section :main_menu, MenuSection, 'section.menu-wrap'
	element :submit_button, '.form-ctrls input.btn'
	element :submit_button_disabled, '.form-ctrls input.btn.disable'
	element :fieldset_errors, 'fieldset.invalid'

	def open_dev_menu
		main_menu.dev_menu.click
	end

	def submit_enabled?
		submit_button.value != 'Fix Errors, Please' &&
		submit_button[:disabled] != true &&
		self.has_submit_button_disabled? == false
	end

	def has_errors?
		self.has_fieldset_errors?
	end
end