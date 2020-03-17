require './bootstrap.rb'

feature 'Template Settings', () => {

  beforeEach(function() {
    cy.auth();
    page = TemplateSettings.new
    page.load()
    cy.hasNoErrors()
  }

  it('shows the Template Settings page', () => {
    page.all_there?.should == true
  }

  it('should load current settings into form fields', () => {
    strict_urls = eeConfig({item: 'strict_urls')
    save_tmpl_revisions = eeConfig({item: 'save_tmpl_revisions')

    page.strict_urls.value.should == strict_urls
    page.site_404.find('div.field-input-selected').contains(eeConfig({item: 'site_404')
    page.save_tmpl_revisions.value.should == save_tmpl_revisions
  }

  it('should validate the form', () => {
    page.max_tmpl_revisions.set 'sdfsdfsd'
    page.submit

    cy.hasNoErrors()
    should_have_form_errors(page)
    page.get('wrap').contains('Attention: Settings not saved'
    page.get('wrap').contains($integer_error
    page.get('wrap').invoke('text').then((text) => {
			expect(text).not.contains( $invalid_path

    // AJAX validation
    page.load()
    page.max_tmpl_revisions.set 'sdfsdfsd'
    page.max_tmpl_revisions.trigger 'blur'
    page.wait_for_error_message_count(1)
    should_have_form_errors(page)
    page.get('wrap').contains($integer_error

    page.max_tmpl_revisions.set '100'
    page.max_tmpl_revisions.trigger 'blur'
    page.wait_for_error_message_count(0)
  }

  it('should save and load the settings', () => {
    page.strict_urls_toggle.click()
    page.site_404_options.choose_radio_option('search/index')
    page.save_tmpl_revisions_toggle.click()
    page.max_tmpl_revisions.set '300'
    page.submit

    page.get('wrap').contains('Preferences Updated'
    page.strict_urls.value.should == 'n'
    page.site_404.find('div.field-input-selected').contains('search/index'
    page.save_tmpl_revisions.value.should == 'y'
    page.max_tmpl_revisions.value.should == '300'
  }
}
