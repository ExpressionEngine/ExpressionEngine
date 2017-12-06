class LicenseSettings < ControlPanelPage

  element :license_file, 'input[name=license_file]'

  def load
    settings_btn.click
    click_link 'License & Registration'
  end
end
