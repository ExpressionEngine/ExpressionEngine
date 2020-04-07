require './bootstrap.rb'

context('Messaging Settings', () => {

  beforeEach(function() {
    cy.auth();
    page = MessagingSettings.new
    page.load()
    cy.hasNoErrors()
  }

  it('shows the Messaging Settings page', () => {
    page.all_there?.should == true
  }

  it('should load current settings into form fields', () => {
    prv_msg_auto_links = eeConfig({item: 'prv_msg_auto_links')

    page.prv_msg_max_chars.invoke('val').then((val) => { expect(val).to.be.equal(eeConfig({item: 'prv_msg_max_chars')
    page.prv_msg_html_format.has_checked_radio(eeConfig({item: 'prv_msg_html_format')).should == true
    page.prv_msg_auto_links.invoke('val').then((val) => { expect(val).to.be.equal(prv_msg_auto_links
    page.prv_msg_upload_path.invoke('val').then((val) => { expect(val).to.be.equal(eeConfig({item: 'prv_msg_upload_path')
    page.prv_msg_max_attachments.invoke('val').then((val) => { expect(val).to.be.equal(eeConfig({item: 'prv_msg_max_attachments')
    page.prv_msg_attach_maxsize.invoke('val').then((val) => { expect(val).to.be.equal(eeConfig({item: 'prv_msg_attach_maxsize')
    page.prv_msg_attach_total.invoke('val').then((val) => { expect(val).to.be.equal(eeConfig({item: 'prv_msg_attach_total')
  }

  it('should validate the form', () => {
    page.prv_msg_max_chars.clear().type('sdfsdfsd'
    page.submit

    cy.hasNoErrors()
    page.hasErrors()
//should_have_form_errors(page)
    page.get('wrap').contains('Attention: Settings not saved'
    page.get('wrap').contains(page.messages.validation.integer_error

    // AJAX validation
    page.load()
    page.prv_msg_max_chars.clear().type('sdfsdfsd'
    page.prv_msg_max_chars.blur()
    page.wait_for_error_message_count(1)
    page.hasError(page.prv_msg_max_chars, page.messages.validation.integer_error)
    page.hasErrors()
//should_have_form_errors(page)

    page.prv_msg_upload_path.clear().type('/dfffds/'
    page.prv_msg_upload_path.blur()
    //page.wait_for_error_message_count(2)
    page.hasError(page.prv_msg_upload_path, page.messages.validation.invalid_path)
    page.hasErrors()
//should_have_form_errors(page)

    page.prv_msg_upload_path.set File.expand_path('support/tmp')
    page.prv_msg_upload_path.blur()
    page.wait_for_error_message_count(1)

    page.prv_msg_upload_path.clear().type('/'
    page.prv_msg_upload_path.blur()
    //page.wait_for_error_message_count(2)
    page.hasError(page.prv_msg_upload_path, $not_writable)
    page.hasErrors()
//should_have_form_errors(page)

    page.prv_msg_max_attachments.clear().type('sdfsdfsd'
    page.prv_msg_max_attachments.blur()
    // page.wait_for_error_message_count(3)
    page.hasError(page.prv_msg_max_attachments, page.messages.validation.integer_error)
    page.hasErrors()
//should_have_form_errors(page)

    page.prv_msg_attach_maxsize.clear().type('sdfsdfsd'
    page.prv_msg_attach_maxsize.blur()
    // page.wait_for_error_message_count4)
    page.hasError(page.prv_msg_attach_maxsize, page.messages.validation.integer_error)
    page.hasErrors()
//should_have_form_errors(page)

    page.prv_msg_attach_total.clear().type('sdfsdfsd'
    page.prv_msg_attach_total.blur()
    page.wait_for_error_message_count(5)
    page.hasError(page.prv_msg_attach_total, page.messages.validation.integer_error)
    page.hasErrors()
//should_have_form_errors(page)

    // Fix everything
    page.prv_msg_max_chars.clear().type('100'
    page.prv_msg_max_chars.blur()
    // page.wait_for_error_message_count4)
    page.hasNoError(page.prv_msg_max_chars)
    page.hasErrors()
//should_have_form_errors(page)

    page.prv_msg_upload_path.set File.expand_path('support/tmp')
    page.prv_msg_upload_path.blur()
    // page.wait_for_error_message_count(3)
    page.hasNoError(page.prv_msg_upload_path)
    page.hasErrors()
//should_have_form_errors(page)

    page.prv_msg_max_attachments.clear().type('100'
    page.prv_msg_max_attachments.blur()
    //page.wait_for_error_message_count(2)
    page.hasNoError(page.prv_msg_max_attachments)
    page.hasErrors()
//should_have_form_errors(page)

    page.prv_msg_attach_maxsize.clear().type('100'
    page.prv_msg_attach_maxsize.blur()
    page.wait_for_error_message_count(1)
    page.hasNoError(page.prv_msg_attach_maxsize)
    page.hasErrors()
//should_have_form_errors(page)

    page.prv_msg_attach_total.clear().type('100'
    page.prv_msg_attach_total.blur()
    page.wait_for_error_message_count(0)
    page.hasNoError(page.prv_msg_attach_total)
    should_have_no_form_errors(page)
  }

  it('should reject XSS', () => {
    page.prv_msg_upload_path.clear().type(page.messages.xss_vector)
    page.submit

    page.hasError(page.prv_msg_upload_path, page.messages.xss_error)
    page.hasErrors()
//should_have_form_errors(page)
  }

  it('should save and load the settings', () => {
    page.prv_msg_max_chars.clear().type('100'
    page.prv_msg_html_format.choose_radio_option('none')
    page.prv_msg_auto_links_toggle.click()
    page.prv_msg_upload_path.set File.expand_path('support/tmp')
    page.prv_msg_max_attachments.clear().type('101'
    page.prv_msg_attach_maxsize.clear().type('102'
    page.prv_msg_attach_total.clear().type('103'
    page.submit

    page.get('wrap').contains('Preferences updated'
    page.prv_msg_max_chars.invoke('val').then((val) => { expect(val).to.be.equal('100'
    page.prv_msg_html_format.filter('[value=none').should == true
    page.prv_msg_auto_links.invoke('val').then((val) => { expect(val).to.be.equal('n'
    page.prv_msg_upload_path.invoke('val').then((val) => { expect(val).to.be.equal(File.expand_path('support/tmp')
    page.prv_msg_max_attachments.invoke('val').then((val) => { expect(val).to.be.equal('101'
    page.prv_msg_attach_maxsize.invoke('val').then((val) => { expect(val).to.be.equal('102'
    page.prv_msg_attach_total.invoke('val').then((val) => { expect(val).to.be.equal('103'
  }
}
