require './bootstrap.rb'

feature 'Profile - Personal Settings', () => {
  beforeEach(function() {
    cy.auth();
    page = Profile::PersonalSettings.new
    page.load()
    cy.hasNoErrors()
  }

  it('should load', () => {
    page.all_there?.should == true
  }
}
