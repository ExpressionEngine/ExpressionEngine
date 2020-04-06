require './bootstrap.rb'

context('Search Log', () => {

  beforeEach(function() {
    cy.auth();

    page = SearchLog.new
    add_member(username: 'johndoe')
  }

  before(:each, :pregen => true) do
    page.generate_data(count: 150, timestamp_min: 26)
    page.generate_data(count: 15, member_id: 8, screen_name: 'johndoe', timestamp_min: 25)
    page.load()
    cy.hasNoErrors()

    // These should always be true at all times if not something has gone wrong
    page.displayed?
    page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Search Logs'
    page.get('keyword_search').should('exist')
    page.should have_submit_button

    page.should have_username_filter
    page.username_filter.click()
    page.should have_username_manual_filter

    // page.should have_site_filter // This will not be present if MSM is diabled or we are running Core

    page.should have_date_filter
    page.date_filter.click()
    page.should have_date_manual_filter

    page.get('perpage_filter').should('exist')
    page.perpage_filter.click()
    page.should have_perpage_manual_filter

    page.hide_filters
  }

  it('shows the Search Logs page', :pregen => true do
    page.should have_remove_all
    page.should have_pagination

    page.perpage_filter.invoke('text').then((text) => { expect(text).to.be.equal("show (25)"

    page.should have(6).pages
    page.pages.map {|name| name.text}.should == ["First", "1", "2", "3", "Next", "Last"]

    page.should have(25).items // Default is 25 per page
  }

  // Confirming phrase search
  it('searches by phrases', :pregen => true do
    our_terms = "Rspec entry for search"

    page.generate_data(count: 1, timestamp_max: 0, terms: our_terms)
    page.load()
    cy.hasNoErrors()

    // Be sane and make sure it's there before we search for it
    page.get('wrap').contains(our_terms

    page.get('keyword_search').clear().type("Rspec"
    page.get('keyword_search').type('{enter}')

    page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Search Results we found 1 results for "Rspec"'
    page.get('keyword_search').invoke('val').then((val) => { expect(val).to.be.equal("Rspec"
    page.get('wrap').contains(our_terms
    page.should('have.length', 1)
  }

  it('shows no results on a failed search', :pregen => true do
    our_terms = "NotFoundHere"

    page.get('keyword_search').set our_terms
    page.get('keyword_search').type('{enter}')

    page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Search Results we found 0 results for "' + our_terms + '"'
    page.get('keyword_search').invoke('val').then((val) => { expect(val).to.be.equal(our_terms
    page.get('wrap').contains(our_terms

    page.get('no_results').should('exist')

    page.should have_username_filter
    page.username_filter.click()
    page.should have_username_manual_filter
    // page.should have_site_filter // This will not be present if MSM is diabled or we are running Core
    page.should have_date_filter
    page.date_filter.click()
    page.should have_date_manual_filter
    page.get('perpage_filter').should('exist')
    page.perpage_filter.click()
    page.should have_perpage_manual_filter
    page.get('pagination').should('not.exist')
    page.should_not have_remove_all
  }


  // Confirming individual filter behavior
  it('filters by username', :pregen => true do
    page.username_filter.click()
    page.wait_until_username_filter_menu_visible
    page.username_filter_menu.click_link "johndoe"

    page.username_filter.invoke('text').then((text) => { expect(text).to.be.equal("username (johndoe)"
    page.should have(15).items
    page.get('pagination').should('not.exist')
  }

  it('filters by custom username', :pregen => true do
    page.username_filter.click()
    page.wait_until_username_manual_filter_visible
    page.username_manual_filter.clear().type("johndoe"
    page.execute_script("$('div.filters input[type=text]').closest('form').submit()")

    page.username_filter.invoke('text').then((text) => { expect(text).to.be.equal("username (johndoe)"
    page.should have(15).items
    page.get('pagination').should('not.exist')
  }

  // @TODO Need data for extra site in order to filter by it
  // it('filters by site', :pregen => true do
  //    page.site_filter.select "foobarbaz"
  //    page.get('keyword_search').type('{enter}')
  #
  //    page.should have(x).items
  // }

  // Since this logs in a user we should have 1 entry!
  it('filters by date', :pregen => true do
    page.generate_data(count: 19, timestamp_max: 22)
    page.load()
    cy.hasNoErrors()

    page.date_filter.click()
    page.wait_until_date_filter_menu_visible(2)
    page.date_filter_menu.click_link "Last 24 Hours"

    page.date_filter.invoke('text').then((text) => { expect(text).to.be.equal("date (Last 24 Hours)"
    page.should have(19).items
  }

  it('can change page size', :pregen => true do
    page.perpage_filter.click()
    page.wait_until_perpage_filter_menu_visible
    page.perpage_filter_menu.click_link "25"

    page.perpage_filter.invoke('text').then((text) => { expect(text).to.be.equal("show (25)"
    page.should have(25).items
    page.should have_pagination
    page.should have(6).pages
    page.pages.map {|name| name.text}.should == ["First", "1", "2", "3", "Next", "Last"]
  }

  it('can set a custom limit', :pregen => true do
    page.perpage_filter.click()
    page.wait_until_perpage_manual_filter_visible
    page.perpage_manual_filter.clear().type("42"
    page.execute_script("$('div.filters a[data-filter-label^=show] + div.sub-menu input[type=text]').parents('form').submit()")

    page.perpage_filter.invoke('text').then((text) => { expect(text).to.be.equal("show (42)"
    page.should have(42).items
    page.should have_pagination
    page.should have(6).pages
    page.pages.map {|name| name.text}.should == ["First", "1", "2", "3", "Next", "Last"]
  }

  // Confirming combining filters work
  it('can combine username and page size filters', :pregen => true do
    page.perpage_filter.click()
    page.wait_until_perpage_filter_menu_visible
    page.perpage_filter_menu.click_link "150"
    cy.hasNoErrors()

    // First, confirm we have both 'admin' and 'johndoe' on same page
    page.perpage_filter.has_select?('perpage', :selected => "150 results")
    page.should have(150).items
    page.should have_pagination
    page.get('wrap').contains("johndoe"
    page.get('wrap').contains("admin"

    // Now, combine the filters
    page.username_filter.click()
    page.wait_until_username_filter_menu_visible
    page.username_filter_menu.click_link "johndoe"

    page.perpage_filter.invoke('text').then((text) => { expect(text).to.be.equal("show (150)"
    page.username_filter.invoke('text').then((text) => { expect(text).to.be.equal("username (johndoe)"
    page.should have(15).items
    page.get('pagination').should('not.exist')
    page.items.should_not have_text "admin"
  }

  it('can combine phrase search with filters', :pregen => true do
    page.perpage_filter.click()
    page.wait_until_perpage_filter_menu_visible
    page.perpage_filter_menu.click_link "150"
    cy.hasNoErrors()

      // First, confirm we have both 'admin' and 'johndoe' on same page
    page.perpage_filter.invoke('text').then((text) => { expect(text).to.be.equal("show (150)"
    page.should have(150).items
    page.should have_pagination
    page.get('wrap').contains("johndoe"
    page.get('wrap').contains("admin"

    // Now, combine the filters
    page.get('keyword_search').clear().type("johndoe"
    page.get('keyword_search').type('{enter}')

    page.perpage_filter.invoke('text').then((text) => { expect(text).to.be.equal("show (150)"
    page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Search Results we found 15 results for "johndoe"'
    page.get('keyword_search').invoke('val').then((val) => { expect(val).to.be.equal("johndoe"
    page.should have(15).items
    page.get('pagination').should('not.exist')
    page.items.should_not have_text "admin"
  }

  // Confirming the log deletion action
  it('can remove a single entry', :pregen => true do
    our_terms = "Rspec entry to be deleted"

    page.generate_data(count: 1, timestamp_max: 0, terms: our_terms)
    page.load()
    cy.hasNoErrors()

    log = page.find('section.item-wrap div.item', :text => our_terms)
    log.find('li.remove a').click() // Activates a modal

    page.get('modal').should('be.visible')
    page.get('modal_title').invoke('text').then((text) => { expect(text).to.be.equal("Confirm Removal"
    page.get('modal').contains("You are attempting to remove the following items, please confirm this action."
    page.get('modal').contains(our_terms
    page.get('modal_submit_button').click() // Submits a form

    page.get('alert').should('be.visible')
    page.get('alert').invoke('text').then((text) => { expect(text).to.be.equal("Logs Deleted 1 log(s) deleted from Search logs"

    page.should have_no_content our_terms
  }

  it('can remove all entries', :pregen => true do
    page.remove_all.click() // Activates a modal

    page.get('modal').should('be.visible')
    page.get('modal_title').invoke('text').then((text) => { expect(text).to.be.equal("Confirm Removal"
    page.get('modal').contains("You are attempting to remove the following items, please confirm this action."
    page.get('modal').contains("Search Logs: All"
    page.get('modal_submit_button').click() // Submits a form

    page.get('alert').should('be.visible')
    page.get('alert').invoke('text').then((text) => { expect(text).to.be.equal("Logs Deleted 165 log(s) deleted from Search logs"

    page.get('no_results').should('exist')
    page.get('pagination').should('not.exist')
  }

  // Confirming Pagination behavior
  it('shows the Prev button when on page 2', :pregen => true do
    click_link "Next"

    page.should have_pagination
    page.should have(7).pages
    page.pages.map {|name| name.text}.should == ["First", "Previous", "1", "2", "3", "Next", "Last"]
  }

  it('does not show Next on the last page', :pregen => true do
    click_link "Last"

    page.should have_pagination
    page.should have(6).pages
    page.pages.map {|name| name.text}.should == ["First", "Previous", "5", "6", "7", "Last"]
  }

  it('does not lose a filter value when paginating', :pregen => true do
    page.perpage_filter.click()
    page.wait_until_perpage_filter_menu_visible
    page.perpage_filter_menu.click_link "25"
    cy.hasNoErrors()

    page.perpage_filter.invoke('text').then((text) => { expect(text).to.be.equal("show (25)"
    page.should have(25).items

    click_link "Next"

    page.perpage_filter.invoke('text').then((text) => { expect(text).to.be.equal("show (25)"
    page.should have(25).items
    page.should have_pagination
    page.should have(7).pages
    page.pages.map {|name| name.text}.should == ["First", "Previous", "1", "2", "3", "Next", "Last"]
  }

  it('will paginate phrase search results', :pregen => true do
    page.generate_data(count: 20, member_id: 2, screen_name: 'johndoe', timestamp_min: 25)

    page.perpage_filter.click()
    page.wait_until_perpage_filter_menu_visible
    page.perpage_filter_menu.click_link "25"
    cy.hasNoErrors()

    page.get('keyword_search').clear().type("johndoe"
    page.get('keyword_search').type('{enter}')
    cy.hasNoErrors()

    // Page 1
    page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Search Results we found 35 results for "johndoe"'
    page.get('keyword_search').invoke('val').then((val) => { expect(val).to.be.equal("johndoe"
    page.items.should_not have_text "admin"
    page.perpage_filter.invoke('text').then((text) => { expect(text).to.be.equal("show (25)"
    page.should have(25).items
    page.should have_pagination
    page.should have(5).pages
    page.pages.map {|name| name.text}.should == ["First", "1", "2", "Next", "Last"]

    click_link "Next"

    // Page 2
    page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Search Results we found 35 results for "johndoe"'
    page.get('keyword_search').invoke('val').then((val) => { expect(val).to.be.equal("johndoe"
    page.items.should_not have_text "admin"
    page.perpage_filter.invoke('text').then((text) => { expect(text).to.be.equal("show (25)"
    page.should have(10).items
    page.should have_pagination
    page.should have(5).pages
    page.pages.map {|name| name.text}.should == ["First", "Previous", "1", "2", "Last"]
  }
}
