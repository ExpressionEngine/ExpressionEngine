require './bootstrap.rb'

context('Word Censorship Settings', () => {

  beforeEach(function() {
    cy.auth();
    page = WordCensorship.new
    page.load()
    cy.hasNoErrors()
  }

  it('shows the Word Censorship Settings page', () => {
    page.all_there?.should == true
  }

  it('should load current settings into form fields', () => {
    enable_censoring = eeConfig({item: 'enable_censoring')
    censor_replacement = eeConfig({item: 'censor_replacement')
    censored_words = eeConfig({item: 'censored_words')

    page.enable_censoring.invoke('val').then((val) => { expect(val).to.be.equal(enable_censoring
    page.censor_replacement.invoke('val').then((val) => { expect(val).to.be.equal(eeConfig({item: 'censor_replacement')
    page.censored_words.invoke('val').then((val) => { expect(val).to.be.equal(eeConfig({item: 'censored_words').gsub('|', "\n")
  }

  it('should reject XSS', () => {
    page.censor_replacement.clear().type(page.messages.xss_vector)
    page.submit

    page.hasError(page.censor_replacement, page.messages.xss_error)
    page.hasErrors()
//should_have_form_errors(page)
  }

  it('should save and load the settings', () => {
    page.enable_censoring_toggle.click()
    page.censor_replacement.clear().type('####'
    page.censored_words.clear().type("Poop\nPerl"
    page.submit

    page.get('wrap').contains('Preferences updated'
    page.enable_censoring.invoke('val').then((val) => { expect(val).to.be.equal('y'
    page.censor_replacement.invoke('val').then((val) => { expect(val).to.be.equal('####'
    page.censored_words.invoke('val').then((val) => { expect(val).to.be.equal("Poop\nPerl"
  }
}
