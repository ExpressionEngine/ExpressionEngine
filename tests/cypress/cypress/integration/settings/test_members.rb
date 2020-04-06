require './bootstrap.rb'

context('Member Settings', () => {

  beforeEach(function() {
    cy.auth();
    page = MemberSettings.new
    page.load()
    cy.hasNoErrors()
  }

  it('shows the Member Settings page', () => {
    page.all_there?.should == true
  }

  it('should load current settings into form fields', () => {
    allow_member_registration = eeConfig({item: 'allow_member_registration')
    require_terms_of_service = eeConfig({item: 'require_terms_of_service')
    allow_member_localization = eeConfig({item: 'allow_member_localization')
    new_member_notification = eeConfig({item: 'new_member_notification')

    page.allow_member_registration.invoke('val').then((val) => { expect(val).to.be.equal(allow_member_registration
    page.require_terms_of_service.invoke('val').then((val) => { expect(val).to.be.equal(require_terms_of_service
    page.allow_member_localization.invoke('val').then((val) => { expect(val).to.be.equal(allow_member_localization
    page.req_mbr_activation.has_checked_radio(eeConfig({item: 'req_mbr_activation')).should == true
    page.default_member_group.has_checked_radio(eeConfig({item: 'default_member_group')).should == true
    page.member_theme.has_checked_radio(eeConfig({item: 'member_theme')).should == true
    page.memberlist_order_by.has_checked_radio(eeConfig({item: 'memberlist_order_by')).should == true
    page.memberlist_sort_order.has_checked_radio(eeConfig({item: 'memberlist_sort_order')).should == true
    page.memberlist_row_limit.has_checked_radio(eeConfig({item: 'memberlist_row_limit')).should == true
    page.new_member_notification.invoke('val').then((val) => { expect(val).to.be.equal(new_member_notification
    page.mbr_notification_emails.invoke('val').then((val) => { expect(val).to.be.equal(eeConfig({item: 'mbr_notification_emails')
  }

  it('should validate the form', () => {
    emails_error = 'This field must contain all valid email addresses.'

    page.mbr_notification_emails.clear().type('sdfsdfsd'
    page.submit

    cy.hasNoErrors()
    page.hasErrors()
//should_have_form_errors(page)
    page.get('wrap').contains('Attention: Settings not saved'
    page.hasError(page.mbr_notification_emails, emails_error)

    // AJAX validation
    page.load()
    page.mbr_notification_emails.clear().type('sdfsdfsd'
    page.mbr_notification_emails.blur()
    page.wait_for_error_message_count(1)
    page.hasError(page.mbr_notification_emails, emails_error)
    page.hasErrors()
//should_have_form_errors(page)

    page.mbr_notification_emails.clear().type('trey@trey.com, test@test.com'
    page.mbr_notification_emails.blur()
    page.wait_for_error_message_count(0)
    should_have_no_error_text(page.mbr_notification_emails)
    should_have_no_form_errors(page)
  }

  it('should save and load the settings', () => {
    allow_member_registration = eeConfig({item: 'allow_member_registration')
    require_terms_of_service = eeConfig({item: 'require_terms_of_service')
    allow_member_localization = eeConfig({item: 'allow_member_localization')
    new_member_notification = eeConfig({item: 'new_member_notification')

    page.allow_member_registration_toggle.click()
    page.req_mbr_activation.choose_radio_option('none')
    page.require_terms_of_service_toggle.click()
    page.allow_member_localization_toggle.click()
    page.default_member_group.choose_radio_option('1')
    page.member_theme.choose_radio_option('default')
    page.memberlist_order_by.choose_radio_option('dates')
    page.memberlist_sort_order.choose_radio_option('asc')
    page.memberlist_row_limit.choose_radio_option('50')
    page.new_member_notification_toggle.click()
    page.mbr_notification_emails.clear().type('test@test.com'
    page.submit

    page.get('wrap').contains('Preferences Updated'
    page.allow_member_registration.value.should_not == allow_member_registration
    page.req_mbr_activation.has_checked_radio('none').should == true
    page.require_terms_of_service.value.should_not == require_terms_of_service
    page.allow_member_localization.value.should_not == allow_member_localization
    page.default_member_group.has_checked_radio('1').should == true
    page.member_theme.has_checked_radio('default').should == true
    page.memberlist_order_by.has_checked_radio('dates').should == true
    page.memberlist_sort_order.has_checked_radio('asc').should == true
    page.memberlist_row_limit.has_checked_radio('50').should == true
    page.new_member_notification.value.should_not == new_member_notification
    page.mbr_notification_emails.invoke('val').then((val) => { expect(val).to.be.equal('test@test.com'
  }
}
