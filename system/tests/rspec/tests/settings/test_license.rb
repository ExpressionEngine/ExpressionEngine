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
    field_required = "This field is required."
    license_invalid_error = 'The license number provided is not a valid license number.'

    @page.license_contact.set ''
    @page.submit

    no_php_js_errors
    should_have_form_errors(@page)
    @page.should have_text 'An error occurred'
    should_have_error_text(@page.license_contact, field_required)
    should_have_no_error_text(@page.license_number)

    @page.load
    @page.license_number.set ''
    @page.submit

    no_php_js_errors
    should_have_form_errors(@page)
    @page.should have_text 'An error occurred'
    should_have_error_text(@page.license_number, field_required)
    should_have_no_error_text(@page.license_contact)

    # AJAX validation
    @page.load
    @page.license_contact.set ''
    @page.license_contact.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_form_errors(@page)
    should_have_error_text(@page.license_contact, field_required)
    should_have_no_error_text(@page.license_number)

    @page.license_contact.set 'ellislab.developers@gmail.com'
    @page.license_contact.trigger 'blur'
    @page.wait_for_error_message_count(0)
    should_have_no_form_errors(@page)
    should_have_no_error_text(@page.license_contact)
    should_have_no_error_text(@page.license_number)

    @page.license_number.set ''
    @page.license_number.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_form_errors(@page)
    should_have_error_text(@page.license_number, field_required)
    should_have_no_error_text(@page.license_contact)

    @page.license_number.set '1234-1234-1234-123'
    @page.license_number.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_form_errors(@page)
    should_have_error_text(@page.license_number, license_invalid_error)
    should_have_no_error_text(@page.license_contact)

    @page.license_number.set '1234-1234-1234-1234'
    @page.license_number.trigger 'blur'
    @page.wait_for_error_message_count(0)
    should_have_no_form_errors(@page)
    should_have_no_error_text(@page.license_contact)
    should_have_no_error_text(@page.license_number)
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