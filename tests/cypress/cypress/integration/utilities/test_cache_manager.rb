require './bootstrap.rb'

feature 'Cache Manager', () => {

  beforeEach(function() {
    cy.auth();
    CacheManager::visit
  }

  it('shows the Cache Manager page', () => {
    page.get('wrap').contains('Cache Manager'
    page.get('wrap').contains('Caches to clear'
    page.should have_checked_field 'All Caches'
    page.get('wrap').invoke('text').then((text) => {
			expect(text).not.contains( 'An error occurred'
  }

  it('should successfully submit with one cache type selected', () => {
    CacheManager::button.click()
    cy.hasNoErrors()

    page.get('wrap').contains('Caches cleared'
  }
}
