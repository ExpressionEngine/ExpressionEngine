class ControlPanelPage < SitePrism::Page

	section :main_menu, MenuSection, 'section.menu-wrap'
	element :submit_button, '.form-ctrls input.btn'

	def open_dev_menu
		main_menu.dev_menu.click
	end
end