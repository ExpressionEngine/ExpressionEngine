require './bootstrap.rb'

feature 'Publish Page - Edit', () => {
  beforeEach(function(){
    cy.auth();
    page = Publish.new
    cy.hasNoErrors()
  }

  it('shows a 404 with no given entry_id', () => {
    page.load()
    page.is_404?.should == true
  }
}
