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

    @page.license_contact.set ''
    @page.submit

    no_php_js_errors
    should_have_form_errors(@page)
    @page.should have_text 'Attention: Settings not saved'
    should_have_error_text(@page.license_contact, $required_error)
    should_have_no_error_text(@page.license_number)

    @page.load
    @page.license_number.set ''
    @page.submit

    no_php_js_errors
    should_have_form_errors(@page)
    @page.should have_text 'Attention: Settings not saved'
    should_have_error_text(@page.license_number, $required_error)
    should_have_no_error_text(@page.license_contact)

    # AJAX validation
    @page.load
    @page.license_contact.set ''
    @page.license_contact.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_form_errors(@page)
    should_have_error_text(@page.license_contact, $required_error)
    should_have_no_error_text(@page.license_number)

    @page.license_contact.set 'ellislab.developers@gmail.com'
    @page.license_contact.trigger 'blur'
    @page.wait_for_error_message_count(0)
    should_have_no_form_errors(@page)
    should_have_no_error_text(@page.license_contact)
    should_have_no_error_text(@page.license_number)

    @page.load
    @page.license_contact.set 'sdfsdfss'
    @page.license_contact.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_form_errors(@page)
    should_have_error_text(@page.license_contact, 'This field must contain a valid email address.')
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
    should_have_error_text(@page.license_number, $required_error)
    should_have_no_error_text(@page.license_contact)

    @page.license_number.set '1234-5678-9123-4567'
    @page.license_number.trigger 'blur'
    @page.wait_for_error_message_count(0)
    should_have_no_form_errors(@page)
    should_have_no_error_text(@page.license_contact)
    should_have_no_error_text(@page.license_number)

    @page.license_number.set '1234-5678-9123-456'
    @page.license_number.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_form_errors(@page)
    should_have_error_text(@page.license_number, 'The license number provided is not a valid license number.')
    should_have_no_error_text(@page.license_contact)
  end

  it 'should reject XSS' do
    @page.license_contact_name.set $xss_vector
    @page.license_contact_name.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_error_text(@page.license_contact_name, $xss_error)
    should_have_form_errors(@page)

    @page.license_contact.set $xss_vector
    @page.license_contact.trigger 'blur'
    @page.wait_for_error_message_count(2)
    should_have_error_text(@page.license_contact, $xss_error)
    should_have_form_errors(@page)
  end

  it 'should load and save the settings' do
    license_contact_name = ee_config(item: 'license_contact_name')
    license_contact = ee_config(item: 'license_contact')
    license_number = ee_config(item: 'license_number')

    @page.license_contact_name.value.should == license_contact_name
    @page.license_contact.value.should == license_contact
    @page.license_number.value.should == license_number

    @page.license_contact_name.set 'Kevin Cupp'
    @page.license_contact.set 'kevin.cupp@gmail.com'
    @page.license_number.set '4321-4321-4321-4321'
    @page.submit

    @page.should have_text 'License & Registration Updated'
    @page.license_contact_name.value.should == 'Kevin Cupp'
    @page.license_contact.value.should == 'kevin.cupp@gmail.com'
    @page.license_number.value.should == '4321-4321-4321-4321'

    ee_config(item: 'license_contact_name', value: license_contact_name)
    ee_config(item: 'license_contact', value: license_contact)
    ee_config(item: 'license_number', value: license_number)
  end

end