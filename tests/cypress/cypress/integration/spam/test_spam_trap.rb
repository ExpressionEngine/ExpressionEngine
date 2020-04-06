require './bootstrap.rb'

context('Spam Module', () => {

  describe "Installation" do

    it('can install from addon manager', () => {
      cy.auth();
      page = AddonManager.new
      page.load()
      spam_row = page.find('div.tbl-wrap table tr', :text => 'Spam')
      spam_row.find('a', :text => 'Install').click()

      cy.hasNoErrors()
    }
  }

  describe "Spam Trap Table" do

    beforeEach(function() {
      cy.auth();

      // preload the spam trap
        $db.query(IO.read('support/spam/spam.sql'))
      clear_db_result

      page = SpamTrap.new
      page.load()

      page.displayed?
      page.get('heading').text.contains('All SPAM'
      page.get('keyword_search').should('exist')
    }

    it('can search by phrases', () => {
      page.get('keyword_search').clear().type('about'
      page.get('keyword_search').type('{enter}')
      cy.hasNoErrors()

      page.get('heading').invoke('text').then((text) => { expect(text).to.be.equal('Search Results we found 5 results for "about"'
      page.get('keyword_search').invoke('val').then((val) => { expect(val).to.be.equal('about'
      page.get('wrap').contains('about'
    }

    it('can mark as ham', () => {
      page.find('.check-ctrl input[type="checkbox"]').check()
      page.get('bulk_action').should('be.visible')
      page.get('bulk_action').select "approve"
      page.get('action_submit_button').click()
      cy.hasNoErrors()
    }

    it('can mark as spam', () => {
      page.find('.check-ctrl input[type="checkbox"]').check()
      page.get('bulk_action').should('be.visible')
      page.get('bulk_action').select "approve"
      page.get('action_submit_button').click()
      cy.hasNoErrors()
    }

  }

}
