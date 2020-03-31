require './bootstrap.rb'

context('Avatar Settings', () => {

  beforeEach(function() {
    cy.auth();
    page = AvatarSettings.new
    page.load()
    cy.hasNoErrors()

    @upload_path = File.expand_path('../../images')
  }

  it('shows the Avatar Settings page', () => {
    page.all_there?.should == true
  }

  it('should load current settings into form fields', () => {
    enable_avatars = eeConfig({item: 'enable_avatars')
    allow_avatar_uploads = eeConfig({item: 'allow_avatar_uploads')

    page.enable_avatars.value.should == enable_avatars
    page.allow_avatar_uploads.value.should == allow_avatar_uploads
    page.avatar_url.value.should == eeConfig({item: 'avatar_url')
    page.avatar_path.value.should == eeConfig({item: 'avatar_path')
    page.avatar_max_width.value.should == eeConfig({item: 'avatar_max_width')
    page.avatar_max_height.value.should == eeConfig({item: 'avatar_max_height')
    page.avatar_max_kb.value.should == eeConfig({item: 'avatar_max_kb')
  }

  it('should validate the form', () => {
    page.avatar_path.clear().type('sdfsdfsd'
    page.submit

    cy.hasNoErrors()
    should_have_form_errors(page)
    page.get('wrap').contains('Attention: Settings not saved'
    page.get('wrap').contains($invalid_path

    // AJAX validation
    page.load()
    page.avatar_path.clear().type('sdfsdfsd'
    page.avatar_path.trigger 'blur'
    page.wait_for_error_message_count(1)
    should_have_error_text(page.avatar_path, $invalid_path)
    should_have_form_errors(page)

    page.avatar_path.set @upload_path
    page.avatar_path.trigger 'blur'
    page.wait_for_error_message_count(0)

    page.avatar_path.clear().type('/'
    page.avatar_path.trigger 'blur'
    page.wait_for_error_message_count(1)
    should_have_error_text(page.avatar_path, $not_writable)
    should_have_form_errors(page)

    page.avatar_max_width.clear().type('dfsd'
    page.avatar_max_width.trigger 'blur'
    page.wait_for_error_message_count(2)
    should_have_error_text(page.avatar_max_width, $integer_error)
    should_have_form_errors(page)

    page.avatar_max_height.clear().type('dsfsd'
    page.avatar_max_height.trigger 'blur'
    page.wait_for_error_message_count(3)
    should_have_error_text(page.avatar_max_height, $integer_error)
    should_have_form_errors(page)

    page.avatar_max_kb.clear().type('sdfsdfsd'
    page.avatar_max_kb.trigger 'blur'
    page.wait_for_error_message_count(4)
    should_have_error_text(page.avatar_max_kb, $integer_error)
    should_have_form_errors(page)

    // Fix everything
    page.avatar_path.set @upload_path
    page.avatar_path.trigger 'blur'
    page.wait_for_error_message_count(3)
    should_have_no_error_text(page.avatar_path)
    should_have_form_errors(page)

    page.avatar_max_width.clear().type('100'
    page.avatar_max_width.trigger 'blur'
    page.wait_for_error_message_count(2)
    should_have_no_error_text(page.avatar_max_width)
    should_have_form_errors(page)

    page.avatar_max_height.clear().type('100'
    page.avatar_max_height.trigger 'blur'
    page.wait_for_error_message_count(1)
    should_have_no_error_text(page.avatar_max_height)
    should_have_form_errors(page)

    page.avatar_max_kb.clear().type('100'
    page.avatar_max_kb.trigger 'blur'
    page.wait_for_error_message_count(0)
    should_have_no_error_text(page.avatar_max_kb)
    should_have_no_form_errors(page)
  }

  it('should reject XSS', () => {
    page.avatar_url.set $xss_vector
    page.avatar_url.trigger 'blur'
    page.wait_for_error_message_count(1)
    should_have_error_text(page.avatar_url, $xss_error)
    should_have_form_errors(page)

    page.avatar_path.set $xss_vector
    page.avatar_path.trigger 'blur'
    page.wait_for_error_message_count(2)
    should_have_error_text(page.avatar_url, $xss_error)
    should_have_error_text(page.avatar_path, $xss_error)
    should_have_form_errors(page)
  }

  it('should save and load the settings', () => {
    enable_avatars = eeConfig({item: 'enable_avatars')
    allow_avatar_uploads = eeConfig({item: 'allow_avatar_uploads')

    page.enable_avatars_toggle.click()
    page.allow_avatar_uploads_toggle.click()
    page.avatar_url.clear().type('http://hello'
    page.avatar_path.set @upload_path
    page.avatar_max_width.clear().type('100'
    page.avatar_max_height.clear().type('101'
    page.avatar_max_kb.clear().type('102'
    page.submit

    page.get('wrap').contains('Preferences updated'
    page.enable_avatars.value.should_not == enable_avatars
    page.allow_avatar_uploads.value.should_not == allow_avatar_uploads
    page.avatar_url.value.should == 'http://hello'
    page.avatar_path.value.should == @upload_path
    page.avatar_max_width.value.should == '100'
    page.avatar_max_height.value.should == '101'
    page.avatar_max_kb.value.should == '102'
  }
}
