require './bootstrap.rb'

feature 'General Settings' do

  before(:each) do
    cp_session
    @page = GeneralSettings.new
    @page.load
    no_php_js_errors
  end

  after(:each) do
    # Reset is_system_on value in config
    ee_config(item: 'is_system_on', value: 'y')
  end

  it 'shows the General Settings page' do
    @page.should have_text 'General Settings'
    @page.should have_site_name
    @page.should have_site_short_name
    @page.should have_is_system_on_y
    @page.should have_is_system_on_n
    @page.should have_new_version_check_y
    @page.should have_new_version_check_n
    # @page.should have_cp_theme
    @page.should have_language
    @page.should have_tz_country
    @page.should have_timezone
    @page.should have_date_format
    @page.should have_time_format
    @page.should have_no_check_version_btn
    @page.should have_include_seconds
    @page.should have_include_seconds_toggle
  end

  describe "form validation" do
    before do
      @error_text = 'This field is required.'
    end

    it 'should validate with submit' do
      # Set other random things to make sure they're repopulated
      @page.is_system_on_n.click
      @page.new_version_check_n.click
      @page.should have_check_version_btn
      @page.date_format_yyyy_mm_dd.click
      @page.time_format_24_hr.click
      @page.include_seconds_toggle.click

      # Only field that's required, will be our test case
      @page.site_name.set ''
      @page.site_short_name.set ''

      @page.submit

      no_php_js_errors
      should_have_form_errors(@page)
      @page.should have_text 'Attention: Settings not saved'
      should_have_error_text(@page.site_name, @error_text)
      should_have_error_text(@page.site_short_name, @error_text)
      @page.is_system_on_n.checked?.should == true
      @page.new_version_check_n.checked?.should == true
      @page.date_format_yyyy_mm_dd.checked?.should == true
      @page.time_format_24_hr.checked?.should == true
      @page.include_seconds.value.should == 'y'
    end

    # AJAX validation
    it "should validate with ajax" do
      # Make sure old values didn't save after validation error
      should_have_no_form_errors(@page)
      should_have_no_error_text(@page.site_name)
      @page.is_system_on_y.checked?.should == true
      @page.new_version_check_y.checked?.should == true
      @page.date_format_mm_dd_yyyy.checked?.should == true
      @page.time_format_12_hr.checked?.should == true
      @page.include_seconds.value.should == 'n'

      # Blank Title
      test_field(@page.site_name, '', @error_text)
      test_field(@page.site_name, 'EE2')

      # Blank Short Name
      test_field(@page.site_short_name, '', @error_text)
      test_field(@page.site_short_name, 'default_site')

      # Short name with spaces
      test_field(@page.site_short_name, 'default site', 'This field may only contain alpha-numeric characters, underscores, and dashes.')
      test_field(@page.site_short_name, 'default_site')

      # Short name with special characters
      test_field(@page.site_short_name, 'default_$ite', 'This field may only contain alpha-numeric characters, underscores, and dashes.')
      test_field(@page.site_short_name, 'default_site')

      # XSS
      test_field(@page.site_name, '"><script>alert(\'stored xss\')<%2fscript>', $xss_error)
      test_field(@page.site_name, 'EE2')

      test_field(@page.site_name, '<script>alert(\'stored xss\')</script>', $xss_error)
      test_field(@page.site_name, 'EE2')

      @page.submit
      should_have_no_form_errors(@page)
      @page.should have_text 'Preferences updated'
    end
  end

  # Tests a given field by giving it a value and seeing if the error matches
  #
  # @param field [Object] The field to test
  # @param value [String] The value to set
  # @param error [String] The error message if one is expected, otherwise leave
  #   empty
  def test_field(field, value, error = false)
    field.set value
    field.trigger 'blur'

    no_php_js_errors

    if error
      @page.wait_for_error_message_count(1)
      should_have_form_errors(@page)
      should_have_error_text(field, error)
    else
      @page.wait_for_error_message_count(0)
      should_have_no_form_errors(@page)
      should_have_no_error_text(field)
    end
  end

  it 'should load and save the settings' do
    # Save new settings
    @page.site_name.set 'My sweet site'
    @page.site_short_name.set 'my_sweet_site'
    @page.is_system_on_n.click
    @page.new_version_check_n.click
    @page.should have_check_version_btn
    @page.date_format_yyyy_mm_dd.click
    @page.time_format_24_hr.click
    @page.include_seconds_toggle.click
    @page.submit

    # Make sure they stuck, also test Check Now button visibility
    no_php_js_errors
    should_have_no_form_errors(@page)
    @page.should have_text 'Preferences updated'
    @page.site_name.value.should == 'My sweet site'
    @page.site_short_name.value.should == 'my_sweet_site'
    @page.is_system_on_n.checked?.should == true
    @page.new_version_check_n.checked?.should == true
    @page.should have_check_version_btn
    @page.date_format_yyyy_mm_dd.checked?.should == true
    @page.time_format_24_hr.checked?.should == true
    @page.include_seconds.value.should == 'y'
  end

  it 'should check for new versions of EE manually' do
    @page.new_version_check_n.click
    @page.should have_check_version_btn
    @page.check_version_btn.click

    # For now, we'll just check to make sure there are no errors
    # getting the latest version info; unsure at the moment how to
    # best handle actual version comparison because we need to edit
    # Core.php dynamically based on the actual latest version
    @page.should have_no_css 'div.banner.issue'
    @page.should have_no_text 'An error occurred'
    @page.should have_no_text 'Unable to determine if a newer version is available at this time.'
  end

end
