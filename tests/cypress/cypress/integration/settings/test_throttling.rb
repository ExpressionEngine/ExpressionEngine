require './bootstrap.rb'

context('Access Throttling Settings', () => {

  beforeEach(function() {
    cy.auth();
    page = ThrottlingSettings.new
    page.load()
    cy.hasNoErrors()
  }

  it('shows the Access Throttling Settings page', () => {
    page.all_there?.should == true
  }

  it('should load current settings into form fields', () => {
    page.enable_throttling.invoke('val').then((val) => { expect(val).to.be.equal(eeConfig({item: 'enable_throttling')
    page.banish_masked_ips.invoke('val').then((val) => { expect(val).to.be.equal(eeConfig({item: 'banish_masked_ips')
    page.lockout_time.invoke('val').then((val) => { expect(val).to.be.equal(eeConfig({item: 'lockout_time')
    page.max_page_loads.invoke('val').then((val) => { expect(val).to.be.equal(eeConfig({item: 'max_page_loads')
    page.time_interval.invoke('val').then((val) => { expect(val).to.be.equal(eeConfig({item: 'time_interval')
    page.banishment_type.has_checked_radio(eeConfig({item: 'banishment_type')).should == true
    page.banishment_url.invoke('val').then((val) => { expect(val).to.be.equal(eeConfig({item: 'banishment_url')
    page.banishment_message.invoke('val').then((val) => { expect(val).to.be.equal(eeConfig({item: 'banishment_message')
  }

  it('should validate the form', () => {
    integer_error = 'This field must contain an integer.'

    page.lockout_time.clear().type('sdfsdfsd'
    page.submit

    cy.hasNoErrors()
    page.hasErrors()
//should_have_form_errors(page)
    page.get('wrap').contains('Attention: Settings not saved'
    page.hasError(page.lockout_time, integer_error)

    // AJAX validation
    page.load()
    page.lockout_time.clear().type('sdfsdfsd'
    page.lockout_time.blur()
    page.wait_for_error_message_count(1)
    page.hasError(page.lockout_time, integer_error)
    page.hasErrors()
//should_have_form_errors(page)

    page.max_page_loads.clear().type('sdfsdfsd'
    page.max_page_loads.blur()
    //page.wait_for_error_message_count(2)
    page.hasError(page.max_page_loads, integer_error)
    page.hasErrors()
//should_have_form_errors(page)

    page.time_interval.clear().type('sdfsdfsd'
    page.time_interval.blur()
    // page.wait_for_error_message_count(3)
    page.hasError(page.time_interval, integer_error)
    page.hasErrors()
//should_have_form_errors(page)

    // Fix everything
    page.lockout_time.clear().type('5'
    page.lockout_time.blur()
    //page.wait_for_error_message_count(2)
    should_have_no_error_text(page.lockout_time)
    page.hasErrors()
//should_have_form_errors(page)

    page.max_page_loads.clear().type('15'
    page.max_page_loads.blur()
    page.wait_for_error_message_count(1)
    should_have_no_error_text(page.max_page_loads)
    page.hasErrors()
//should_have_form_errors(page)

    page.time_interval.clear().type('8'
    page.time_interval.blur()
    page.wait_for_error_message_count(0)
    should_have_no_error_text(page.time_interval)
    should_have_no_form_errors(page)
  }

  it('should reject XSS', () => {
    page.banishment_url.clear().type(page.messages.xss_vector)
    page.banishment_url.blur()
    page.wait_for_error_message_count(1)
    page.hasError(page.banishment_url, page.messages.xss_error)
    page.hasErrors()
//should_have_form_errors(page)

    page.banishment_message.clear().type(page.messages.xss_vector)
    page.banishment_message.blur()
    //page.wait_for_error_message_count(2)
    page.hasError(page.banishment_url, page.messages.xss_error)
    page.hasError(page.banishment_message, page.messages.xss_error)
    page.hasErrors()
//should_have_form_errors(page)
  }

  it('should save and load the settings', () => {
    page.enable_throttling_toggle.click()
    page.banish_masked_ips_toggle.click()
    page.lockout_time.clear().type('60'
    page.max_page_loads.clear().type('40'
    page.time_interval.clear().type('30'
    page.banishment_type.choose_radio_option('404')
    page.banishment_url.clear().type('http://yahoo.com'
    page.banishment_message.clear().type('You are banned'
    page.submit

    page.enable_throttling.invoke('val').then((val) => { expect(val).to.be.equal('y'
    page.banish_masked_ips.invoke('val').then((val) => { expect(val).to.be.equal('n'
    page.lockout_time.invoke('val').then((val) => { expect(val).to.be.equal('60'
    page.max_page_loads.invoke('val').then((val) => { expect(val).to.be.equal('40'
    page.time_interval.invoke('val').then((val) => { expect(val).to.be.equal('30'
    page.banishment_type.has_checked_radio('404').should == true
    page.banishment_url.invoke('val').then((val) => { expect(val).to.be.equal('http://yahoo.com'
    page.banishment_message.invoke('val').then((val) => { expect(val).to.be.equal('You are banned'
  }
}
