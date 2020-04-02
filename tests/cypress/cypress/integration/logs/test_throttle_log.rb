require './bootstrap.rb'

context('Throttling Log', () => {

  beforeEach(function() {
    cy.auth();

    page = ThrottleLog.new
  }

  before(:each, :pregen => true) do
    page.generate_data(count: 150)
    page.generate_data(count: 100, locked_out: true)
  }

  before(:each, :enabled => false) do
    eeConfig({item: 'enable_throttling', value: 'n')
    page.load()
    cy.hasNoErrors()

    // These should always be true at all times if not something has gone wrong
    page.displayed?
    page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Access Throttling Logs'

    page.should_not have_keyword_search
    page.should_not have_submit_button
    page.should_not have_perpage_filter
  }

  before(:each, :enabled => true) do
    eeConfig({item: 'enable_throttling', value: 'y')
    page.load()
    cy.hasNoErrors()

    // These should always be true at all times if not something has gone wrong
    page.displayed?
    page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Access Throttling Logs'
    page.should have_keyword_search
    page.should have_submit_button
    page.should have_perpage_filter
  }

  context('when throttling is disabled', () => {
    it('shows the Turn Throttling On button', :enabled => false, :pregen => true do
      page.get('no_results').should('exist')
      page.should have_selector('a', :text => 'Turn Throttling On')
    }
  }

  context('when throttling is enabled', () => {
    it('shows the Access Throttling Logs page', :enabled => true, :pregen => true do
      page.should have_remove_all
      page.should have_pagination

      page.perpage_filter.invoke('text').then((text) => { expect(text).to.be.equal("show (25)"

      page.should have(6).pages
      page.pages.map {|name| name.text}.should == ["First", "1", "2", "3", "Next", "Last"]

      page.should have(25).items // Default is 25 per page
    }

    // Confirming phrase search
    it('searches by phrases', :enabled => true, :pregen => true do
      our_ip = "172.16.11.42"

      page.generate_data(count: 1, timestamp_max: 0, ip_address: our_ip)
      page.load()

      // Be sane and make sure it's there before we search for it
      page.get('wrap').contains(our_ip

      page.get('keyword_search').clear().type("172.16.11"
      page.get('keyword_search').send_keys(:enter)

      page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Search Results we found 1 results for "172.16.11"'
      page.get('keyword_search').value.should eq "172.16.11"
      page.get('wrap').contains(our_ip
      page.should('have.length', 1)
    }

    it('shows no results on a failed search', :enabled => true, :pregen => true do
      our_ip = "NotFoundHere"

      page.get('keyword_search').set our_ip
      page.get('keyword_search').send_keys(:enter)

      page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Search Results we found 0 results for "' + our_ip + '"'
      page.get('keyword_search').value.should eq our_ip
      page.get('wrap').contains(our_ip
      page.should have_perpage_filter

      page.get('no_results').should('exist')

      page.should_not have_pagination
      page.should_not have_remove_all
    }

    it('can change page size', :enabled => true, :pregen => true do
      page.perpage_filter.click()
      page.wait_until_perpage_filter_menu_visible
      page.perpage_filter_menu.click_link "25 results"

      page.perpage_filter.invoke('text').then((text) => { expect(text).to.be.equal("show (25)"
      page.should have(25).items
      page.should have_pagination
      page.should have(6).pages
      page.pages.map {|name| name.text}.should == ["First", "1", "2", "3", "Next", "Last"]
    }

    it('can set a custom limit', :enabled => true, :pregen => true do
      page.perpage_filter.click()
      page.wait_until_perpage_manual_filter_visible
      page.perpage_manual_filter.clear().type("42"
      page.execute_script("$('div.filters input[type=text]').closest('form').submit()")

      page.perpage_filter.invoke('text').then((text) => { expect(text).to.be.equal("show (42)"
      page.should have(42).items
      page.should have_pagination
      page.should have(6).pages
      page.pages.map {|name| name.text}.should == ["First", "1", "2", "3", "Next", "Last"]
    }

    it('can combine phrase search with filters', :enabled => true, :pregen => true do
      our_ip = "172.16.11.42"

      page.generate_data(count: 27, timestamp_max: 0, ip_address: our_ip)
      page.load()
      cy.hasNoErrors()

      page.perpage_filter.click()
      page.wait_until_perpage_filter_menu_visible
      page.perpage_filter_menu.click_link "25"
      cy.hasNoErrors()

      page.get('keyword_search').clear().type("172.16.11"
      page.get('keyword_search').send_keys(:enter)

      page.perpage_filter.invoke('text').then((text) => { expect(text).to.be.equal("show (25)"
      page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Search Results we found 27 results for "172.16.11"'
      page.get('keyword_search').value.should eq "172.16.11"
      page.get('wrap').contains(our_ip
      page.should have(25).items
      page.should have_pagination
      page.should have(5).pages
      page.pages.map {|name| name.text}.should == ["First", "1", "2", "Next", "Last"]
    }

    // Confirming the log deletion action
    // it('can remove a single entry', :enabled => true, :pregen => true do
    //    our_action = "Rspec entry to be deleted"
    #
    //    page.generate_data(count: 1, timestamp_max: 0, action: our_action)
    //    page.load()
    #
    //    log = page.find('section.item-wrap div.item', :text => our_action)
    //    log.find('li.remove a').click()
    #
    //    page.should have_alert
    //    page.should have_no_content our_action
    // }

    it('can remove all entries', :enabled => true, :pregen => true do
      page.remove_all.click() // Activates a modal

      page.get('modal').should('be.visible')
      page.get('modal_title').invoke('text').then((text) => { expect(text).to.be.equal("Confirm Removal"
      page.get('modal').contains("You are attempting to remove the following items, please confirm this action."
      page.get('modal').contains("Access Throttling Logs: All"
      page.get('modal_submit_button').click() // Submits a form

      page.should have_alert
      page.get('alert').invoke('text').then((text) => { expect(text).to.be.equal("Logs Deleted 250 log(s) deleted from Throttling logs"

      page.get('no_results').should('exist')
      page.should_not have_pagination
    }

    // Confirming Pagination behavior
    it('shows the Prev button when on page 2', :enabled => true, :pregen => true do
      click_link "Next"

      page.should have_pagination
      page.should have(7).pages
      page.pages.map {|name| name.text}.should == ["First", "Previous", "1", "2", "3", "Next", "Last"]
    }

    it('does not show Next on the last page', :enabled => true, :pregen => true do
      click_link "Last"

      page.should have_pagination
      page.should have(6).pages
      page.pages.map {|name| name.text}.should == ["First", "Previous", "8", "9", "10", "Last"]
    }

    it('does not lose a filter value when paginating', :enabled => true, :pregen => true do
      page.perpage_filter.click()
      page.wait_until_perpage_filter_menu_visible
      page.perpage_filter_menu.click_link "25 results"
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

    it('will paginate phrase search results', :enabled => true, :pregen => true do
      page.generate_data(count: 35, timestamp_max: 0, ip_address: "172.16.11.42")
      page.load()
      cy.hasNoErrors()

      page.perpage_filter.click()
      page.wait_until_perpage_filter_menu_visible
      page.perpage_filter_menu.click_link "25"
      cy.hasNoErrors()

      page.get('keyword_search').clear().type("172.16.11"
      page.get('keyword_search').send_keys(:enter)
      cy.hasNoErrors()

      // Page 1
      page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Search Results we found 35 results for "172.16.11"'
      page.get('keyword_search').value.should eq "172.16.11"
      page.items.should_not have_text "10.0"
      page.perpage_filter.invoke('text').then((text) => { expect(text).to.be.equal("show (25)"
      page.should have(25).items
      page.should have_pagination
      page.should have(5).pages
      page.pages.map {|name| name.text}.should == ["First", "1", "2", "Next", "Last"]

      click_link "Next"

      // Page 2
      page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Search Results we found 35 results for "172.16.11"'
      page.get('keyword_search').value.should eq "172.16.11"
      page.items.should_not have_text "10.0"
      page.perpage_filter.invoke('text').then((text) => { expect(text).to.be.equal("show (25)"
      page.should have(10).items
      page.should have_pagination
      page.should have(5).pages
      page.pages.map {|name| name.text}.should == ["First", "Previous", "1", "2", "Last"]
    }
  }
}
