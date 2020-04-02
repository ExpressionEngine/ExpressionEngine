require './bootstrap.rb'

context('Debug Extensions', () => {

  beforeEach(function() {
    cy.auth();

    page = DebugExtensions.new
    page.load()

    page.displayed?
    page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Manage Add-on Extensions'

    page.should have_addons
  }

  it('shows the Manage Add-on Extensions page', () => {
    page.addon_name_header[:class].should eq 'highlight'
    page.should have(2).addons // RTE + Header
  }

  // it('can sort by name'
  // it('can sort by status'

  it('can disable and enable an extension', () => {
    page.statuses[0].invoke('text').then((text) => { expect(text).to.be.equal('ENABLED'

    // Disable an add-on
    page.checkbox_header.find('input[type="checkbox"]').check()
    page.get('bulk_action').should('be.visible')
    page.bulk_action.select "Disable"
    page.get('action_submit_button').click()
    cy.hasNoErrors()

    page.statuses[0].invoke('text').then((text) => { expect(text).to.be.equal('DISABLED'

    // Enable an add-on
    page.checkbox_header.find('input[type="checkbox"]').check()
    page.get('bulk_action').should('be.visible')
    page.bulk_action.select "Enable"
    page.get('action_submit_button').click()
    cy.hasNoErrors()

    page.statuses[0].invoke('text').then((text) => { expect(text).to.be.equal('ENABLED'
  }

  it('can navigate to a manual page', () => {
    page.find('ul.toolbar li.manual a').click()
  }

}
