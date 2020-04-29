require './bootstrap.rb'
require 'date'

ENV['TZ'] = 'US/Eastern' // For date/time calculations

context('Communicate > Sent', () => {

  beforeEach(function() {
    cy.auth();
    page = CommunicateSent.new
  }

  def load_page
    page.load()
    cy.hasNoErrors()

    page.should be_displayed
    page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Sent Emails'
    page.should have_phrase_search
    page.should have_search_submit_button
    page.should have_email_table
  }

  it('shows the sent Emails page (with no results)', () => {
    page.load()
    cy.hasNoErrors()

    page.should be_displayed
    page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Sent Emails'
    page.should have_phrase_search
    page.should have_search_submit_button
    page.should have_email_table
    page.get('no_results').should('exist')
    page.get('wrap').contains('No Sent emails found'
    page.get('wrap').contains('Create new Email'
    page.get('pagination').should('not.exist')
  }

  it('sorts by subject (asc)', () => {
    subjects = []

    ('A'..'Z').each do |l|
      subjects.push(l)
      page.generate_data(subject: l, count: 1)
    }
    load_page

    page.find('th.highlight').invoke('text').then((text) => { expect(text).to.be.equal('Subject'
    page.find('th.highlight').should have_css 'a.sort.asc'
    page.subjects.map {|subject| subject.text}.should == subjects[0..19]
    page.should have(21).rows // +1 for the header
  }
  it('sorts by subject (desc)', () => {
    subjects = []
    ('A'..'Z').each do |l|
      subjects.push(l)
      page.generate_data(subject: l, count: 1)
    }
    subjects.reverse!
    load_page
    page.subject_header.find('a.sort').click()
    cy.hasNoErrors()
    page.find('th.highlight').invoke('text').then((text) => { expect(text).to.be.equal('Subject'
    page.find('th.highlight').should have_css 'a.sort.desc'
    page.subjects.map {|subject| subject.text}.should == subjects[0..19]
    page.should have(21).rows // +1 for the header
  }
  it('sorts by date (asc)', () => {
    now = Time.now
    dates = []
    (0...25).each do |n|
      my_date = now - (n * 86400)
      dates.push(my_date.to_datetime.strftime("%-m/%-d/%Y %-l:%M %p"))
      page.generate_data(timestamp: my_date.to_i, count: 1)
    }
    dates.reverse!
    load_page
    page.date_header.find('a.sort').click()
    cy.hasNoErrors()
    page.find('th.highlight').invoke('text').then((text) => { expect(text).to.be.equal('Date'
    page.find('th.highlight').should have_css 'a.sort.asc'
    page.dates.map {|date| date.text}.should == dates[0..19]
    page.should have(21).rows // +1 for the header
  }
  it('sorts by date (desc)', () => {
    now = Time.now
    dates = []
    (0...25).each do |n|
      my_date = now - (n * 86400)
      dates.push(my_date.to_datetime.strftime("%-m/%-d/%Y %-l:%M %p"))
      page.generate_data(timestamp: my_date.to_i, count: 1)
    }
    load_page
    page.date_header.find('a.sort').click() // To sort by date
    cy.hasNoErrors()
    page.date_header.find('a.sort').click() // DESC sort
    cy.hasNoErrors()

    page.find('th.highlight').invoke('text').then((text) => { expect(text).to.be.equal('Date'
    page.find('th.highlight').should have_css 'a.sort.desc'
    page.dates.map {|date| date.text}.should == dates[0..19]
    page.should have(21).rows // +1 for the header
  }
  it('sorts by total sent (asc)', () => {
    sent = []
    (1..25).each do |n|
      sent.push(n.toString())
      page.generate_data(total_sent: n, count: 1)
    }
    load_page
    page.total_sent_header.find('a.sort').click()
    cy.hasNoErrors()
    page.find('th.highlight').invoke('text').then((text) => { expect(text).to.be.equal('Total Sent'
    page.find('th.highlight').should have_css 'a.sort.asc'
    page.total_sents.map {|sent| sent.text}.should == sent[0..19]
    page.should have(21).rows // +1 for the header
  }
  it('sorts by total sent (desc)', () => {
    sent = []
    (1..25).each do |n|
      sent.push(n.toString())
      page.generate_data(total_sent: n, count: 1)
    }
    sent.reverse!
    load_page
    page.total_sent_header.find('a.sort').click() // To sort by total sent
    cy.hasNoErrors()
    page.total_sent_header.find('a.sort').click() // DESC sort
    cy.hasNoErrors()
    page.find('th.highlight').invoke('text').then((text) => { expect(text).to.be.equal('Total Sent'
    page.find('th.highlight').should have_css 'a.sort.desc'
    page.total_sents.map {|sent| sent.text}.should == sent[0..19]
    page.should have(21).rows // +1 for the header
  }
  it('can search by subject', () => {
    phrase = "Zeppelins"
    data = phrase + " are cool"
    page.generate_data
    page.generate_data(count: 5, subject: data)
    load_page
    page.phrase_search.set phrase
    page.search_submit_button.click()
    cy.hasNoErrors()
    page.should_not have_no_results
    page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Search Results we found 5 results for "' + phrase + '"'
    page.phrase_searchinvoke('val').then((val) => { expect(val).to.be.equal(phrase
    page.get('wrap').contains(data
    page.should have(6).rows #+1 for the header
  }
  it('can search by message', () => {
    phrase = "Zeppelins"
    data = phrase + " are cool"
    page.generate_data
    page.generate_data(count: 5, message: data)
    load_page
    page.phrase_search.set phrase
    page.search_submit_button.click()
    cy.hasNoErrors()
    page.should_not have_no_results
    page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Search Results we found 5 results for "' + phrase + '"'
    page.phrase_searchinvoke('val').then((val) => { expect(val).to.be.equal(phrase
    page.should have(6).rows #+1 for the header
  }
  it('can search by from name', () => {
    phrase = "Zeppelin"
    data = "Ferdinand von Zeppelin"
    page.generate_data
    page.generate_data(count: 5, from_name: data)
    load_page
    page.phrase_search.set phrase
    page.search_submit_button.click()
    cy.hasNoErrors()
    page.should_not have_no_results
    page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Search Results we found 5 results for "' + phrase + '"'
    page.phrase_searchinvoke('val').then((val) => { expect(val).to.be.equal(phrase
    page.should have(6).rows #+1 for the header
  }
  it('can search by from email', () => {
    phrase = "zeppelin"
    data = "ferdinand.von.zeppelin@airships.de"
    page.generate_data
    page.generate_data(count: 5, from_email: data)
    load_page
    page.phrase_search.set phrase
    page.search_submit_button.click()
    cy.hasNoErrors()
    page.should_not have_no_results
    page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Search Results we found 5 results for "' + phrase + '"'
    page.phrase_searchinvoke('val').then((val) => { expect(val).to.be.equal(phrase
    page.should have(6).rows #+1 for the header
  }
  it('can search by recipient', () => {
    phrase = "zeppelin"
    data = "ferdinand.von.zeppelin@airships.de"
    page.generate_data
    page.generate_data(count: 5, recipient: data)
    load_page
    page.phrase_search.set phrase
    page.search_submit_button.click()
    cy.hasNoErrors()
    page.should_not have_no_results
    page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Search Results we found 5 results for "' + phrase + '"'
    page.phrase_searchinvoke('val').then((val) => { expect(val).to.be.equal(phrase
    page.should have(6).rows #+1 for the header
  }
  it('can search by cc', () => {
    phrase = "zeppelin"
    data = "ferdinand.von.zeppelin@airships.de"
    page.generate_data
    page.generate_data(count: 5, cc: data)
    load_page
    page.phrase_search.set phrase
    page.search_submit_button.click()
    cy.hasNoErrors()
    page.should_not have_no_results
    page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Search Results we found 5 results for "' + phrase + '"'
    page.phrase_searchinvoke('val').then((val) => { expect(val).to.be.equal(phrase
    page.should have(6).rows #+1 for the header
  }
  it('can search by bcc', () => {
    phrase = "zeppelin"
    data = "ferdinand.von.zeppelin@airships.de"
    page.generate_data
    page.generate_data(count: 5, bcc: data)
    load_page
    page.phrase_search.set phrase
    page.search_submit_button.click()
    cy.hasNoErrors()
    page.should_not have_no_results
    page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Search Results we found 5 results for "' + phrase + '"'
    page.phrase_searchinvoke('val').then((val) => { expect(val).to.be.equal(phrase
    page.should have(6).rows #+1 for the header
  }
  it('displays "no results" when searching returns nothing', () => {
    phrase = "Zeppelins"
    page.generate_data
    load_page
    page.phrase_search.set phrase
    page.search_submit_button.click()
    cy.hasNoErrors()
    page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Search Results we found 0 results for "' + phrase + '"'
    page.phrase_searchinvoke('val').then((val) => { expect(val).to.be.equal(phrase
    page.get('no_results').should('exist')
  }
  it('maintains sort when searching', () => {
    page.generate_data
    phrase = "Zeppelins"
    data = phrase + " are cool"
    sent = []
    (1..25).each do |n|
      total_sent = n + Random.rand(42)
      sent.push(total_sent)
      page.generate_data(subject: data, total_sent: total_sent, count: 1)
    }
    sent.sort!
    load_page
    page.total_sent_header.find('a.sort').click()
    cy.hasNoErrors()
    page.phrase_search.set phrase
    page.search_submit_button.click()
    cy.hasNoErrors()
    page.find('th.highlight').invoke('text').then((text) => { expect(text).to.be.equal('Total Sent'
    page.find('th.highlight').should have_css 'a.sort.asc'
    page.should_not have_no_results
    page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Search Results we found 20 results for "' + phrase + '"'
    page.phrase_searchinvoke('val').then((val) => { expect(val).to.be.equal(phrase
    page.get('wrap').contains(data
    page.total_sents.map {|sent| sent.text}.should == sent[0..19].map {|n| n.toString()}
    page.should have(21).rows // +1 for the header
  }
  it('will not pagingate at 25 or under', () => {
    page.generate_data(count: 25)
    load_page
    page.get('pagination').should('not.exist')
  }

  it('will paginate at over 26 emails', () => {
    page.generate_data(count: 26)
    load_page

    page.should have_pagination
      page.should have(5).pages
      page.pages.map {|name| name.text}.should == ["First", "1", "2", "Next", "Last"]
  }

  it('will show the Prev button when on page 2', () => {
    page.generate_data
    load_page

      click_link "Next"
    cy.hasNoErrors()

    page.should have_pagination
      page.should have(7).pages
      page.pages.map {|name| name.text}.should == ["First", "Previous", "1", "2", "3", "Next", "Last"]
  }

  it('will now show Next on the last page', () => {
    page.generate_data
    load_page

      click_link "Last"
    cy.hasNoErrors()

    page.should have_pagination
      page.should have(6).pages
      page.pages.map {|name| name.text}.should == ["First", "Previous", "8", "9", "10", "Last"]
  }

  it('maintains sort while paging', () => {
    page.generate_data
    load_page

    page.total_sent_header.find('a.sort').click()
    cy.hasNoErrors()

      click_link "Next"
    cy.hasNoErrors()

    page.find('th.highlight').invoke('text').then((text) => { expect(text).to.be.equal('Total Sent'
    page.find('th.highlight').should have_css 'a.sort.asc'
  }

  it('maintains search while paging', () => {
    phrase = "Zeppelins"
    data = phrase + " are cool"
    page.generate_data(subject: "Albatross")
    page.generate_data(subject: data)
    load_page

    page.phrase_search.set phrase
    page.search_submit_button.click()
    cy.hasNoErrors()

      click_link "Next"
    cy.hasNoErrors()

    page.should_not have_no_results
    page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Search Results we found 20 results for "' + phrase + '"'
    page.phrase_searchinvoke('val').then((val) => { expect(val).to.be.equal(phrase
    page.get('wrap').contains(data
    page.should_not have_text "Albatross"
  }

  it('maintains sort and search while paging', () => {
    phrase = "Zeppelins"
    data = phrase + " are cool"
    page.generate_data(subject: "Albatross")
    page.generate_data(subject: data)
    load_page

    page.phrase_search.set phrase
    page.search_submit_button.click()
    cy.hasNoErrors()

    page.total_sent_header.find('a.sort').click()
    cy.hasNoErrors()

      click_link "Next"
    cy.hasNoErrors()

    page.should_not have_no_results
    page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Search Results we found 20 results for "' + phrase + '"'
    page.phrase_searchinvoke('val').then((val) => { expect(val).to.be.equal(phrase
    page.get('wrap').contains(data
    page.should_not have_text "Albatross"
    page.find('th.highlight').invoke('text').then((text) => { expect(text).to.be.equal('Total Sent'
    page.find('th.highlight').should have_css 'a.sort.asc'
  }

  it('resets the page on a new sort', () => {
    page.generate_data
    load_page

    page.should have_pagination
      page.should have(6).pages
      page.pages.map {|name| name.text}.should == ["First", "1", "2", "3", "Next", "Last"]
    page.pagination.find('a.act').invoke('text').then((text) => { expect(text).to.be.equal('1'

    click_link "Next"
    cy.hasNoErrors()

      page.pages.map {|name| name.text}.should == ["First", "Previous", "1", "2", "3", "Next", "Last"]
    page.pagination.find('a.act').invoke('text').then((text) => { expect(text).to.be.equal('2'

    page.total_sent_header.find('a.sort').click()
    cy.hasNoErrors()

      page.pages.map {|name| name.text}.should == ["First", "1", "2", "3", "Next", "Last"]
    page.pagination.find('a.act').invoke('text').then((text) => { expect(text).to.be.equal('1'
  }

  it('resets the page on a new search', () => {
    phrase = "Zeppelins"
    data = phrase + " are cool"

    page.generate_data(subject: data)
    load_page

    page.should have_pagination
      page.should have(6).pages
      page.pages.map {|name| name.text}.should == ["First", "1", "2", "3", "Next", "Last"]
    page.pagination.find('a.act').invoke('text').then((text) => { expect(text).to.be.equal('1'

    click_link "Next"
    cy.hasNoErrors()

      page.pages.map {|name| name.text}.should == ["First", "Previous", "1", "2", "3", "Next", "Last"]
    page.pagination.find('a.act').invoke('text').then((text) => { expect(text).to.be.equal('2'

    page.phrase_search.set phrase
    page.search_submit_button.click()
    cy.hasNoErrors()

      page.pages.map {|name| name.text}.should == ["First", "1", "2", "3", "Next", "Last"]
    page.pagination.find('a.act').invoke('text').then((text) => { expect(text).to.be.equal('1'
  }

  it('can view an email', () => {
    my_subject = 'Rspec utilities/communicate test plain text email'
    my_body = "This a test email sent from the communicate tool."
    test_from = 'ellislab.developers.rspec@mailinator.com'
    email = 'ferdinand.von.zeppelin@airships.de'

    page.generate_data(subject: my_subject, from_email: test_from, recipient: email, cc: email, bcc: email, message: my_body, count: 1)
    load_page

    page.first('ul.toolbar li.view a').click()
    cy.hasNoErrors()

    page.should have_modal
    page.get('modal_title').text.should eq my_subject
    page.get('modal').contains(my_body
  }

  it('can resend an email', () => {
    my_subject = 'Rspec utilities/communicate test plain text email'
    my_body = "This a test email sent from the communicate tool."
    test_from = 'ellislab.developers.rspec@mailinator.com'
    email = 'ferdinand.von.zeppelin@airships.de'

    page.generate_data(subject: my_subject, from_email: test_from, recipient: email, cc: email, bcc: email, message: my_body, count: 1)
    load_page

    page.first('ul.toolbar li.sync a').click()
    cy.hasNoErrors()

    communicate = Communicate.new

    communicate.should be_displayed
    communicate.heading.invoke('text').then((text) => { expect(text).to.be.equal('Communicate'

    communicate.subjectinvoke('val').then((val) => { expect(val).to.be.equal(my_subject
    communicate.from_emailinvoke('val').then((val) => { expect(val).to.be.equal(test_from
    communicate.recipientinvoke('val').then((val) => { expect(val).to.be.equal(email
    communicate.ccinvoke('val').then((val) => { expect(val).to.be.equal(email
    communicate.bccinvoke('val').then((val) => { expect(val).to.be.equal(email
    communicate.bodyinvoke('val').then((val) => { expect(val).to.be.equal(my_body
  }

  it('displays an itemized confirmation modal when removing 5 or less emails', () => {
    phrase = "Zeppelins"
    data = phrase + " are cool"

    page.generate_data(count: 5, subject: data)
    page.generate_data(count: 12)
    load_page

    page.rows.each do |row|
      if row.text.include? data
        row.find('input[type="checkbox"]').check()
      }
    }

    page.get('bulk_action').should('be.visible')
    page.get('bulk_action').select("Remove")
    page.get('action_submit_button').click()

    page.get('modal').should('be.visible')
    page.get('modal_title').invoke('text').then((text) => { expect(text).to.be.equal("Confirm Removal"
    page.get('modal').contains("You are attempting to remove the following items, please confirm this action."
    page.get('modal').contains(data
    page.get('modal').find('.checklist li').length.should eq 5
  }

  it('displays a bulk confirmation modal when removing more than 5 emails', () => {
    phrase = "Zeppelins"
    data = phrase + " are cool"

    page.generate_data(count: 6, subject: data)
    page.generate_data(count: 12)
    load_page

    page.rows.each do |row|
      if row.text.include? data
        row.find('input[type="checkbox"]').check()
      }
    }

    page.get('bulk_action').should('be.visible')
    page.get('bulk_action').select("Remove")
    page.get('action_submit_button').click()

    page.get('modal').should('be.visible')
    page.get('modal_title').invoke('text').then((text) => { expect(text).to.be.equal("Confirm Removal"
    page.get('modal').contains("You are attempting to remove the following items, please confirm this action."
    page.get('modal').contains("Sent Emails: 6 Emails"
  }

  it('can remove emails in bulk', () => {
    phrase = "Zeppelins"
    data = phrase + " are cool"

    page.generate_data(count: 8, subject: data)
    page.generate_data(count: 12)
    load_page

    page.rows.each do |row|
      if row.text.include? data
        row.find('input[type="checkbox"]').check()
      }
    }

    page.get('bulk_action').should('be.visible')
    page.get('bulk_action').select("Remove")
    page.get('action_submit_button').click()
    page.get('modal').should('be.visible')
    page.get('modal_submit_button').click() // Submits a form
    cy.hasNoErrors()
    page.should have(13).rows // +1 for the header
    page.should_not have_text data
  }

}