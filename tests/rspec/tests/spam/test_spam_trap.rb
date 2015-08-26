require './bootstrap.rb'

feature 'Spam Module' do

	describe "Installation" do

		it 'can install from addon manager' do
			cp_session
			@page = AddonManager.new
			@page.load
			@page.phrase_search.set 'spam'
			@page.search_submit_button.click
			no_php_js_errors

			@page.first_party_addons[0].find('ul.toolbar li.install a.add').click
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
			@page.heading.text.should eq 'All SPAM'
			@page.should have_phrase_search
		end

		it 'can search by phrases' do
			@page.phrase_search.set 'test'
			@page.search_submit_button.click
			no_php_js_errors

			@page.heading.text.should eq 'Search Results we found 188 results for "test"'
			@page.phrase_search.value.should eq 'test'
			@page.should have_text 'test'
		end

		it 'can mark as ham' do
			@page.find('.check-ctrl input[type="checkbox"]').set true
			@page.wait_until_bulk_action_visible
			@page.bulk_action.select "approve"
			@page.action_submit_button.click
			no_php_js_errors
		end

		it 'can mark as spam' do
			@page.find('.check-ctrl input[type="checkbox"]').set true
			@page.wait_until_bulk_action_visible
			@page.bulk_action.select "approve"
			@page.action_submit_button.click
			no_php_js_errors
		end

		it 'can reverse sort by Content' do
			a_to_z_addons = @page.content.map {|spam| spam.text}

			@page.content_header.find('a.sort').click
			no_php_js_errors

			@page.content_header[:class].should eq 'highlight'
			@page.content.map {|addon| addon.text}.should == a_to_z_addons.reverse!
		end

	end

end
