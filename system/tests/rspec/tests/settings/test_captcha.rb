require './bootstrap.rb'

feature 'CAPTCHA Settings' do

  before(:each) do
    cp_session
    @page = CaptchaSettings.new
    @page.load
    no_php_js_errors

    @upload_path = File.expand_path('../../../images')
  end

  it 'shows the Avatar Settings page' do
    @page.all_there?.should == true
  end

  it 'should load current settings into form fields' do
    require_captcha = ee_config(item: 'require_captcha')
    captcha_font = ee_config(item: 'captcha_font')
    captcha_rand = ee_config(item: 'captcha_rand')
    captcha_require_members = ee_config(item: 'captcha_require_members')

    @page.require_captcha_y.checked?.should == (require_captcha == 'y')
    @page.require_captcha_n.checked?.should == (require_captcha == 'n')
    @page.captcha_font_y.checked?.should == (captcha_font == 'y')
    @page.captcha_font_n.checked?.should == (captcha_font == 'n')
    @page.captcha_rand_y.checked?.should == (captcha_rand == 'y')
    @page.captcha_rand_n.checked?.should == (captcha_rand == 'n')
    @page.captcha_require_members_y.checked?.should == (captcha_require_members == 'y')
    @page.captcha_require_members_n.checked?.should == (captcha_require_members == 'n')
    @page.captcha_url.value.should == ee_config(item: 'captcha_url')
    @page.captcha_path.value.should == ee_config(item: 'captcha_path')
  end

  it 'should validate the form' do
    invalid_path = 'The path you submitted is not valid.'
    not_writable = 'The path you submitted is not writable.'

    @page.captcha_path.set 'sdfsdfsd'
    @page.submit

    no_php_js_errors
    should_have_form_errors(@page)
    @page.should have_text 'Attention: Settings not saved'
    should_have_error_text(@page.captcha_path, invalid_path)

    # AJAX validation
    @page.load
    @page.captcha_path.set 'sdfsdfsd'
    @page.captcha_path.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_error_text(@page.captcha_path, invalid_path)
    should_have_form_errors(@page)

    @page.captcha_path.set @upload_path
    @page.captcha_path.trigger 'blur'
    @page.wait_for_error_message_count(0)

    @page.captcha_path.set '/'
    @page.captcha_path.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_error_text(@page.captcha_path, not_writable)
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
    @page.require_captcha_y.click
    @page.captcha_font_n.click
    @page.captcha_rand_n.click
    @page.captcha_require_members_y.click
    @page.captcha_url.set 'http://hello'
    @page.captcha_path.set @upload_path
    @page.submit

    @page.should have_text 'Preferences updated'
    @page.require_captcha_y.checked?.should == true
    @page.captcha_font_n.checked?.should == true
    @page.captcha_rand_n.checked?.should == true
    @page.captcha_require_members_y.checked?.should == true
    @page.captcha_url.value.should == 'http://hello'
    @page.captcha_path.value.should == @upload_path
  end
end