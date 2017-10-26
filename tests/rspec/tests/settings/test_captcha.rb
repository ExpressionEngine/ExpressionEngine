require './bootstrap.rb'

feature 'CAPTCHA Settings' do

  before(:each) do
    cp_session
    @page = CaptchaSettings.new
    @page.load
    no_php_js_errors

    @upload_path = File.expand_path('../../images')
  end

  it 'shows the CAPTCHA Settings page' do
    @page.all_there?.should == true
  end

  it 'should load current settings into form fields' do
    require_captcha = ee_config(item: 'require_captcha')
    captcha_font = ee_config(item: 'captcha_font')
    captcha_rand = ee_config(item: 'captcha_rand')
    captcha_require_members = ee_config(item: 'captcha_require_members')

    @page.require_captcha.value.should == require_captcha
    @page.captcha_font.value.should == captcha_font
    @page.captcha_rand.value.should == captcha_rand
    @page.captcha_require_members.value.should == captcha_require_members
    @page.captcha_url.value.should == ee_config(item: 'captcha_url')
    @page.captcha_path.value.should == ee_config(item: 'captcha_path')
  end

  it 'should validate the form' do
    @page.captcha_path.set 'sdfsdfsd'
    @page.submit

    no_php_js_errors
    should_have_form_errors(@page)
    @page.should have_text 'Attention: Settings not saved'
    should_have_error_text(@page.captcha_path, $invalid_path)

    # AJAX validation
    @page.load
    @page.captcha_path.set 'sdfsdfsd'
    @page.captcha_path.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_error_text(@page.captcha_path, $invalid_path)
    should_have_form_errors(@page)

    @page.captcha_path.set @upload_path
    @page.captcha_path.trigger 'blur'
    @page.wait_for_error_message_count(0)

    @page.captcha_path.set '/'
    @page.captcha_path.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_error_text(@page.captcha_path, $not_writable)
    should_have_form_errors(@page)
  end

  it 'should reject XSS' do
    @page.captcha_url.set $xss_vector
    @page.captcha_url.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_error_text(@page.captcha_url, $xss_error)
    should_have_form_errors(@page)

    @page.captcha_path.set $xss_vector
    @page.captcha_path.trigger 'blur'
    @page.wait_for_error_message_count(2)
    should_have_error_text(@page.captcha_url, $xss_error)
    should_have_error_text(@page.captcha_path, $xss_error)
    should_have_form_errors(@page)
  end

  it 'should save and load the settings' do
    require_captcha = ee_config(item: 'require_captcha')
    captcha_font = ee_config(item: 'captcha_font')
    captcha_rand = ee_config(item: 'captcha_rand')
    captcha_require_members = ee_config(item: 'captcha_require_members')

    @page.require_captcha_toggle.click
    @page.captcha_font_toggle.click
    @page.captcha_rand_toggle.click
    @page.captcha_require_members_toggle.click
    @page.captcha_url.set 'http://hello'
    @page.captcha_path.set @upload_path
    @page.submit

    @page.should have_text 'Preferences updated'
    @page.require_captcha.value.should_not == require_captcha
    @page.captcha_font.value.should_not == captcha_font
    @page.captcha_rand.value.should_not == captcha_rand
    @page.captcha_require_members.value.should_not == captcha_require_members
    @page.captcha_url.value.should == 'http://hello'
    @page.captcha_path.value.should == @upload_path
  end
end
