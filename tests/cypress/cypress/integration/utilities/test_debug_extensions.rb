require './bootstrap.rb'

feature 'Debug Extensions', () => {

  beforeEach(function() {
    cy.auth();

    page = DebugExtensions.new
    page.load()

    page.displayed?
    page.heading.text.should eq 'Manage Add-on Extensions'

    page.should have_addons
  }

  it('shows the Manage Add-on Extensions page', () => {
    page.addon_name_header[:class].should eq 'highlight'
    page.should have(2).addons // RTE + Header
  }

  // it('can sort by name'
  // it('can sort by status'

  it('can disable and enable an extension', () => {
    page.statuses[0].text.should eq 'ENABLED'

    // Disable an add-on
    page.checkbox_header.find('input[type="checkbox"]').set true
    page.wait_until_bulk_action_visible
    page.bulk_action.select "Disable"
    page.action_submit_button.click()
    cy.hasNoErrors()

    page.statuses[0].text.should eq 'DISABLED'

    // Enable an add-on
    page.checkbox_header.find('input[type="checkbox"]').set true
    page.wait_until_bulk_action_visible
    page.bulk_action.select "Enable"
    page.action_submit_button.click()
    cy.hasNoErrors()

    page.statuses[0].text.should eq 'ENABLED'
  }

  it('can navigate to a manual page', () => {
    page.find('ul.toolbar li.manual a').click()
  }

}
