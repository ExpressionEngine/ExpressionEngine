require './bootstrap.rb'

feature 'Cache Manager', () => {

  beforeEach(function() {
    cy.auth();
    CacheManager::visit
  }

  it('shows the Cache Manager page', () => {
    page.should have_text 'Cache Manager'
    page.should have_text 'Caches to clear'
    page.should have_checked_field 'All Caches'
    page.should have_no_text 'An error occurred'
  }

  it('should successfully submit with one cache type selected', () => {
    CacheManager::button.click()
    cy.hasNoErrors()

    page.should have_text 'Caches cleared'
  }
}
