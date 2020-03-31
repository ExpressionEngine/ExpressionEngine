require './bootstrap.rb'

context('Member Field List', () => {

  beforeEach(function() {
    cy.auth();
    page = MemberFields.new
    page.load()
    cy.hasNoErrors()
  }

  it('shows the Member Field List page', () => {
    page.all_there?.should == true
  }
}
