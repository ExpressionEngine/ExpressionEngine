require './bootstrap.rb'

feature 'Spam Module' do

  describe "Installation" do

    it 'can install from addon manager' do
      cp_session
      @page = AddonManager.new
      @page.load
      spam_row = @page.find('table.app-listing tr', :text => 'Spam')
      spam_row.find('a', :text => 'Install').click

      no_php_js_errors
    end
  end

  describe "Spam Trap Table" do

    before(:each) do
      cp_session

      # preload the spam trap
        $db.query(IO.read('support/spam/spam.sql'))
      clear_db_result

      @page = SpamTrap.new
      @page.load

      @page.displayed?
      @page.heading.text.should have_text 'All SPAM'
      @page.should have_keyword_search
    end

    it 'can search by phrases' do
      @page.keyword_search.set 'about'
      @page.keyword_search.send_keys(:enter)
      no_php_js_errors

      @page.heading.text.should eq 'Search Results we found 5 results for "about"'
      @page.keyword_search.value.should eq 'about'
      @page.should have_text 'about'
    end

    it 'can mark as ham' do
      @page.find('th input[type="checkbox"]').set true
      @page.wait_until_bulk_action_visible
      @page.bulk_action.select "approve"
      @page.action_submit_button.click
      no_php_js_errors
    end

    it 'can mark as spam' do
      @page.find('th input[type="checkbox"]').set true
      @page.wait_until_bulk_action_visible
      @page.bulk_action.select "approve"
      @page.action_submit_button.click
      no_php_js_errors
    end

  end

end
