class LicenseSettings < ControlPanelPage

	element :license_contact_name, 'input[name=license_contact_name]'
	element :license_contact, 'input[name=license_contact]'
	element :license_number, 'input[name=license_number]'

	def load
		settings_btn.click
		click_link 'License & Registration'
	end
end