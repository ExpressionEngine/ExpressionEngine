require './bootstrap.rb'

feature 'Spam Module', () => {

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
      page.heading.text.contains('All SPAM'
      page.should have_keyword_search
    }

    it('can search by phrases', () => {
      page.keyword_search.set 'about'
      page.keyword_search.send_keys(:enter)
      cy.hasNoErrors()

      page.heading.text.should eq 'Search Results we found 5 results for "about"'
      page.keyword_search.value.should eq 'about'
      page.get('wrap').contains('about'
    }

    it('can mark as ham', () => {
      page.find('.check-ctrl input[type="checkbox"]').set true
      page.wait_until_bulk_action_visible
      page.bulk_action.select "approve"
      page.action_submit_button.click()
      cy.hasNoErrors()
    }

    it('can mark as spam', () => {
      page.find('.check-ctrl input[type="checkbox"]').set true
      page.wait_until_bulk_action_visible
      page.bulk_action.select "approve"
      page.action_submit_button.click()
      cy.hasNoErrors()
    }

  }

}
