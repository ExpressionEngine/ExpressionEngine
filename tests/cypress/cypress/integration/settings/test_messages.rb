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

    page.prv_msg_max_chars.value.should == eeConfig({item: 'prv_msg_max_chars')
    page.prv_msg_html_format.has_checked_radio(eeConfig({item: 'prv_msg_html_format')).should == true
    page.prv_msg_auto_links.value.should == prv_msg_auto_links
    page.prv_msg_upload_path.value.should == eeConfig({item: 'prv_msg_upload_path')
    page.prv_msg_max_attachments.value.should == eeConfig({item: 'prv_msg_max_attachments')
    page.prv_msg_attach_maxsize.value.should == eeConfig({item: 'prv_msg_attach_maxsize')
    page.prv_msg_attach_total.value.should == eeConfig({item: 'prv_msg_attach_total')
  }

  it('should validate the form', () => {
    page.prv_msg_max_chars.clear().type('sdfsdfsd'
    page.submit

    cy.hasNoErrors()
    should_have_form_errors(page)
    page.get('wrap').contains('Attention: Settings not saved'
    page.get('wrap').contains($integer_error

    // AJAX validation
    page.load()
    page.prv_msg_max_chars.clear().type('sdfsdfsd'
    page.prv_msg_max_chars.trigger 'blur'
    page.wait_for_error_message_count(1)
    should_have_error_text(page.prv_msg_max_chars, $integer_error)
    should_have_form_errors(page)

    page.prv_msg_upload_path.clear().type('/dfffds/'
    page.prv_msg_upload_path.trigger 'blur'
    page.wait_for_error_message_count(2)
    should_have_error_text(page.prv_msg_upload_path, $invalid_path)
    should_have_form_errors(page)

    page.prv_msg_upload_path.set File.expand_path('support/tmp')
    page.prv_msg_upload_path.trigger 'blur'
    page.wait_for_error_message_count(1)

    page.prv_msg_upload_path.clear().type('/'
    page.prv_msg_upload_path.trigger 'blur'
    page.wait_for_error_message_count(2)
    should_have_error_text(page.prv_msg_upload_path, $not_writable)
    should_have_form_errors(page)

    page.prv_msg_max_attachments.clear().type('sdfsdfsd'
    page.prv_msg_max_attachments.trigger 'blur'
    page.wait_for_error_message_count(3)
    should_have_error_text(page.prv_msg_max_attachments, $integer_error)
    should_have_form_errors(page)

    page.prv_msg_attach_maxsize.clear().type('sdfsdfsd'
    page.prv_msg_attach_maxsize.trigger 'blur'
    page.wait_for_error_message_count(4)
    should_have_error_text(page.prv_msg_attach_maxsize, $integer_error)
    should_have_form_errors(page)

    page.prv_msg_attach_total.clear().type('sdfsdfsd'
    page.prv_msg_attach_total.trigger 'blur'
    page.wait_for_error_message_count(5)
    should_have_error_text(page.prv_msg_attach_total, $integer_error)
    should_have_form_errors(page)

    // Fix everything
    page.prv_msg_max_chars.clear().type('100'
    page.prv_msg_max_chars.trigger 'blur'
    page.wait_for_error_message_count(4)
    should_have_no_error_text(page.prv_msg_max_chars)
    should_have_form_errors(page)

    page.prv_msg_upload_path.set File.expand_path('support/tmp')
    page.prv_msg_upload_path.trigger 'blur'
    page.wait_for_error_message_count(3)
    should_have_no_error_text(page.prv_msg_upload_path)
    should_have_form_errors(page)

    page.prv_msg_max_attachments.clear().type('100'
    page.prv_msg_max_attachments.trigger 'blur'
    page.wait_for_error_message_count(2)
    should_have_no_error_text(page.prv_msg_max_attachments)
    should_have_form_errors(page)

    page.prv_msg_attach_maxsize.clear().type('100'
    page.prv_msg_attach_maxsize.trigger 'blur'
    page.wait_for_error_message_count(1)
    should_have_no_error_text(page.prv_msg_attach_maxsize)
    should_have_form_errors(page)

    page.prv_msg_attach_total.clear().type('100'
    page.prv_msg_attach_total.trigger 'blur'
    page.wait_for_error_message_count(0)
    should_have_no_error_text(page.prv_msg_attach_total)
    should_have_no_form_errors(page)
  }

  it('should reject XSS', () => {
    page.prv_msg_upload_path.set $xss_vector
    page.submit

    should_have_error_text(page.prv_msg_upload_path, $xss_error)
    should_have_form_errors(page)
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
    page.prv_msg_max_chars.value.should == '100'
    page.prv_msg_html_format.has_checked_radio('none').should == true
    page.prv_msg_auto_links.value.should == 'n'
    page.prv_msg_upload_path.value.should == File.expand_path('support/tmp')
    page.prv_msg_max_attachments.value.should == '101'
    page.prv_msg_attach_maxsize.value.should == '102'
    page.prv_msg_attach_total.value.should == '103'
  }
}
