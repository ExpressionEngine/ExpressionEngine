require './bootstrap.rb'

feature 'Word Censorship Settings', () => {

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
    enable_censoring = ee_config(item: 'enable_censoring')
    censor_replacement = ee_config(item: 'censor_replacement')
    censored_words = ee_config(item: 'censored_words')

    page.enable_censoring.value.should == enable_censoring
    page.censor_replacement.value.should == ee_config(item: 'censor_replacement')
    page.censored_words.value.should == ee_config(item: 'censored_words').gsub('|', "\n")
  }

  it('should reject XSS', () => {
    page.censor_replacement.set $xss_vector
    page.submit

    should_have_error_text(page.censor_replacement, $xss_error)
    should_have_form_errors(page)
  }

  it('should save and load the settings', () => {
    page.enable_censoring_toggle.click()
    page.censor_replacement.set '####'
    page.censored_words.set "Poop\nPerl"
    page.submit

    page.should have_text 'Preferences updated'
    page.enable_censoring.value.should == 'y'
    page.censor_replacement.value.should == '####'
    page.censored_words.value.should == "Poop\nPerl"
  }
}
