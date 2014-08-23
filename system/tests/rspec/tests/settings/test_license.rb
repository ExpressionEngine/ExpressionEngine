require './bootstrap.rb'

feature 'License Settings' do

  before(:each) do
    cp_session
    @page = LicenseSettings.new
    @page.load
    no_php_js_errors
  end

  it 'shows the License settings page' do
    @page.should have_text 'License & Registration Settings'
    @page.all_there?.should == true
  end

  it 'should validate the form' do
    contact_error = 'The "Account holder e-mail" field is required.'
    license_reg_error = 'The "License number" field is required.'
    license_invalid_error = 'The license number provided is not a valid license number.'

    @page.license_contact.set ''
    @page.submit

    no_php_js_errors
    should_have_form_errors(@page)
    @page.should have_text 'An error occurred'
    @page.should have_text contact_error
    @page.should have_no_text license_reg_error
    @page.should have_no_text license_invalid_error

    @page.load
    @page.license_number.set ''
    @page.submit

    no_php_js_errors
    should_have_form_errors(@page)
    @page.should have_text 'An error occurred'
    @page.should have_no_text contact_error
    @page.should have_text license_reg_error
    @page.should have_no_text license_invalid_error

    # AJAX validation
    @page.load
    @page.license_contact.set ''
    @page.license_contact.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_form_errors(@page)
    @page.should have_text contact_error

    @page.license_contact.set 'ellislab.developers@gmail.com'
    @page.license_contact.trigger 'blur'
    @page.wait_for_error_message_count(0)
    should_have_no_form_errors(@page)
    @page.should have_no_text contact_error

    @page.license_number.set ''
    @page.license_number.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_form_errors(@page)
    @page.should have_text license_reg_error

    @page.license_number.set '1234-1234-1234-123'
    @page.license_number.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_form_errors(@page)
    @page.should have_text license_invalid_error

    @page.license_number.set '1234-1234-1234-1234'
    @page.license_number.trigger 'blur'
    @page.wait_for_error_message_count(0)
    should_have_no_form_errors(@page)
    @page.should have_no_text license_reg_error
    @page.should have_no_text license_invalid_error
  end

  it 'should load and save the settings' do
    license_contact = ee_config(item: 'license_contact')
    license_number = ee_config(item: 'license_number')

    @page.license_contact.value.should == license_contact
    @page.license_number.value.should == license_number

    @page.license_contact.set 'kevin.cupp@gmail.com'
    @page.license_number.set '4321-4321-4321-4321'
    @page.submit

    @page.should have_text 'License & Registration Updated'
    @page.license_contact.value.should == 'kevin.cupp@gmail.com'
    @page.license_number.value.should == '4321-4321-4321-4321'

    ee_config(item: 'license_contact', value: license_contact)
    ee_config(item: 'license_number', value: license_number)
  end

end