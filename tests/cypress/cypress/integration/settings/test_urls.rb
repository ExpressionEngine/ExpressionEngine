require './bootstrap.rb'

context('URL and Path Settings', () => {

  beforeEach(function() {
    cy.auth();
    page = UrlsSettings.new
    page.load()
    cy.hasNoErrors()

    @site_index = eeConfig({item: 'site_index')
    @site_url = eeConfig({item: 'site_url')
    @cp_url = eeConfig({item: 'cp_url')
    @theme_folder_url = eeConfig({item: 'theme_folder_url')
    @theme_folder_path = eeConfig({item: 'theme_folder_path')
    @profile_trigger = eeConfig({item: 'profile_trigger')
    @reserved_category_word = eeConfig({item: 'reserved_category_word')
    @use_category_name = eeConfig({item: 'use_category_name')
    @word_separator = eeConfig({item: 'word_separator')
  }

  it('shows the URL and Path Settings page', () => {
    page.get('wrap').contains('URL and Path Settings'
    page.get('wrap').contains('Website index page'
    page.all_there?.should == true
  }

  it('should load current path settings into form fields', () => {
    page.site_index.invoke('val').then((val) => { expect(val).to.be.equal(@site_index
    page.site_url.invoke('val').then((val) => { expect(val).to.be.equal(@site_url
    page.cp_url.invoke('val').then((val) => { expect(val).to.be.equal(@cp_url
    page.theme_folder_url.invoke('val').then((val) => { expect(val).to.be.equal(@theme_folder_url
    page.theme_folder_path.invoke('val').then((val) => { expect(val).to.be.equal('{base_path}/themes/'
    page.profile_trigger.invoke('val').then((val) => { expect(val).to.be.equal(@profile_trigger
    page.category_segment_trigger.invoke('val').then((val) => { expect(val).to.be.equal(@reserved_category_word
    page.use_category_name.has_checked_radio(@use_category_name).should == true
    page.url_title_separator.has_checked_radio(@word_separator).should == true
  }

  it('should validate the form', () => {
    field_required = "This field is required."

    page.site_url.clear().type(''
    page.submit

    cy.hasNoErrors()
    page.hasErrors()
//should_have_form_errors(page)
    page.get('wrap').contains('Attention: Settings not saved'
    page.hasError(page.site_url, field_required)

    // AJAX validation
    // Field not required, shouldn't do anything
    page.load()
    page.site_index.clear().type(''
    page.site_index.blur()
    should_have_no_form_errors(page)

    page.site_url.clear().type(''
    page.site_url.blur()
    page.wait_for_error_message_count(1)
    page.hasError(page.site_url, field_required)
    page.hasErrors()
//should_have_form_errors(page)

    page.cp_url.clear().type(''
    page.cp_url.blur()
    //page.wait_for_error_message_count(2)
    page.hasErrors()
//should_have_form_errors(page)
    page.hasError(page.site_url, field_required)
    page.hasError(page.cp_url, field_required)

    page.theme_folder_url.clear().type(''
    page.theme_folder_url.blur()
    // page.wait_for_error_message_count(3)

    page.theme_folder_path.clear().type(''
    page.theme_folder_path.blur()
    // page.wait_for_error_message_count4)

    page.hasErrors()
//should_have_form_errors(page)
    page.hasError(page.site_url, field_required)
    page.hasError(page.cp_url, field_required)
    page.hasError(page.theme_folder_url, field_required)
    page.hasError(page.theme_folder_path, field_required)

    page.theme_folder_path.clear().type('/'
    // When a text field is invalid, shouldn't need to blur
    // page.theme_folder_path.blur()
    // page.wait_for_error_message_count(3)
    // Make sure validation timer is still bound to field
    page.theme_folder_path.clear().type(''
    // page.wait_for_error_message_count4)
    page.theme_folder_path.clear().type('/'
    // page.wait_for_error_message_count(3)
    // Timer should be unbound on blur
    page.theme_folder_path.blur()

    // Invalid theme path
    page.theme_folder_path.clear().type('/dfsdfsdfd'
    page.theme_folder_path.blur()
    // page.wait_for_error_message_count4)

    page.hasErrors()
//should_have_form_errors(page)
    page.hasError(page.site_url, field_required)
    page.hasError(page.cp_url, field_required)
    page.hasError(page.theme_folder_url, field_required)
    // TODO: Uncomment when this stops fluking out
    #page.hasError(page.theme_folder_path, $invalid_path)
  }

  it('should reject XSS', () => {
    page.site_index.clear().type(page.messages.xss_vector)
    page.site_index.blur()
    page.wait_for_error_message_count(1)
    page.hasError(page.site_index, page.messages.xss_error)
    page.hasErrors()
//should_have_form_errors(page)

    page.site_url.clear().type(page.messages.xss_vector)
    page.site_url.blur()
    //page.wait_for_error_message_count(2)
    page.hasError(page.site_url, page.messages.xss_error)
    page.hasErrors()
//should_have_form_errors(page)

    page.cp_url.clear().type(page.messages.xss_vector)
    page.cp_url.blur()
    // page.wait_for_error_message_count(3)
    page.hasErrors()
//should_have_form_errors(page)
    page.hasError(page.site_url, page.messages.xss_error)
    page.hasError(page.cp_url, page.messages.xss_error)

    page.theme_folder_url.clear().type(page.messages.xss_vector)
    page.theme_folder_url.blur()
    // page.wait_for_error_message_count4)

    page.theme_folder_path.clear().type(page.messages.xss_vector)
    page.theme_folder_path.blur()
    page.wait_for_error_message_count(5)

    page.hasErrors()
//should_have_form_errors(page)
    page.hasError(page.site_url, page.messages.xss_error)
    page.hasError(page.cp_url, page.messages.xss_error)
    page.hasError(page.theme_folder_url, page.messages.xss_error)
    page.hasError(page.theme_folder_path, page.messages.xss_error)
  }

  it('should save and load the settings', () => {
    // We'll test one value for now to make sure the form is saving,
    // don't want to be changing values that could break the site
    // after submission
    page.site_index.clear().type('hello.php'
    page.submit

    page.get('wrap').contains('Preferences updated'
    page.site_index.invoke('val').then((val) => { expect(val).to.be.equal('hello.php'

    // Since this is in config.php, reset the value
    eeConfig({item: 'index_page', value: 'index.php')
  }
}
