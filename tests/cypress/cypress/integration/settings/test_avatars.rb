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

    page.enable_avatars.invoke('val').then((val) => { expect(val).to.be.equal(enable_avatars
    page.allow_avatar_uploads.invoke('val').then((val) => { expect(val).to.be.equal(allow_avatar_uploads
    page.avatar_url.invoke('val').then((val) => { expect(val).to.be.equal(eeConfig({item: 'avatar_url')
    page.avatar_path.invoke('val').then((val) => { expect(val).to.be.equal(eeConfig({item: 'avatar_path')
    page.avatar_max_width.invoke('val').then((val) => { expect(val).to.be.equal(eeConfig({item: 'avatar_max_width')
    page.avatar_max_height.invoke('val').then((val) => { expect(val).to.be.equal(eeConfig({item: 'avatar_max_height')
    page.avatar_max_kb.invoke('val').then((val) => { expect(val).to.be.equal(eeConfig({item: 'avatar_max_kb')
  }

  it('should validate the form', () => {
    page.avatar_path.clear().type('sdfsdfsd'
    page.submit

    cy.hasNoErrors()
    page.hasErrors()
//should_have_form_errors(page)
    page.get('wrap').contains('Attention: Settings not saved'
    page.get('wrap').contains(page.messages.validation.invalid_path

    // AJAX validation
    page.load()
    page.avatar_path.clear().type('sdfsdfsd'
    page.avatar_path.blur()
    page.wait_for_error_message_count(1)
    page.hasError(page.avatar_path, page.messages.validation.invalid_path)
    page.hasErrors()
//should_have_form_errors(page)

    page.avatar_path.set @upload_path
    page.avatar_path.blur()
    page.wait_for_error_message_count(0)

    page.avatar_path.clear().type('/'
    page.avatar_path.blur()
    page.wait_for_error_message_count(1)
    page.hasError(page.avatar_path, $not_writable)
    page.hasErrors()
//should_have_form_errors(page)

    page.avatar_max_width.clear().type('dfsd'
    page.avatar_max_width.blur()
    //page.wait_for_error_message_count(2)
    page.hasError(page.avatar_max_width, page.messages.validation.integer_error)
    page.hasErrors()
//should_have_form_errors(page)

    page.avatar_max_height.clear().type('dsfsd'
    page.avatar_max_height.blur()
    // page.wait_for_error_message_count(3)
    page.hasError(page.avatar_max_height, page.messages.validation.integer_error)
    page.hasErrors()
//should_have_form_errors(page)

    page.avatar_max_kb.clear().type('sdfsdfsd'
    page.avatar_max_kb.blur()
    // page.wait_for_error_message_count4)
    page.hasError(page.avatar_max_kb, page.messages.validation.integer_error)
    page.hasErrors()
//should_have_form_errors(page)

    // Fix everything
    page.avatar_path.set @upload_path
    page.avatar_path.blur()
    // page.wait_for_error_message_count(3)
    page.hasNoError(page.avatar_path)
    page.hasErrors()
//should_have_form_errors(page)

    page.avatar_max_width.clear().type('100'
    page.avatar_max_width.blur()
    //page.wait_for_error_message_count(2)
    page.hasNoError(page.avatar_max_width)
    page.hasErrors()
//should_have_form_errors(page)

    page.avatar_max_height.clear().type('100'
    page.avatar_max_height.blur()
    page.wait_for_error_message_count(1)
    page.hasNoError(page.avatar_max_height)
    page.hasErrors()
//should_have_form_errors(page)

    page.avatar_max_kb.clear().type('100'
    page.avatar_max_kb.blur()
    page.wait_for_error_message_count(0)
    page.hasNoError(page.avatar_max_kb)
    should_have_no_form_errors(page)
  }

  it('should reject XSS', () => {
    page.avatar_url.clear().type(page.messages.xss_vector)
    page.avatar_url.blur()
    page.wait_for_error_message_count(1)
    page.hasError(page.avatar_url, page.messages.xss_error)
    page.hasErrors()
//should_have_form_errors(page)

    page.avatar_path.clear().type(page.messages.xss_vector)
    page.avatar_path.blur()
    //page.wait_for_error_message_count(2)
    page.hasError(page.avatar_url, page.messages.xss_error)
    page.hasError(page.avatar_path, page.messages.xss_error)
    page.hasErrors()
//should_have_form_errors(page)
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
    page.avatar_url.invoke('val').then((val) => { expect(val).to.be.equal('http://hello'
    page.avatar_path.invoke('val').then((val) => { expect(val).to.be.equal(@upload_path
    page.avatar_max_width.invoke('val').then((val) => { expect(val).to.be.equal('100'
    page.avatar_max_height.invoke('val').then((val) => { expect(val).to.be.equal('101'
    page.avatar_max_kb.invoke('val').then((val) => { expect(val).to.be.equal('102'
  }
}
