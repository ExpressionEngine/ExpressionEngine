require './bootstrap.rb'
require 'date'

ENV['TZ'] = 'US/Eastern' # For date/time calculations

feature 'Communicate > Sent' do

	before(:each) do
		cp_session
		@page = CommunicateSent.new
	end

	def load_page
		@page.load
		no_php_js_errors

		@page.should be_displayed
		@page.heading.text.should eq 'Sent Emails'
		@page.should have_phrase_search
		@page.should have_search_submit_button
		@page.should have_email_table
	end

	it 'shows the sent Emails page (with no results)' do
		@page.load
		no_php_js_errors

		@page.should be_displayed
		@page.heading.text.should eq 'Sent Emails'
		@page.should have_phrase_search
		@page.should have_search_submit_button
		@page.should have_email_table
		@page.should have_no_results
		@page.should have_text 'No Emails availble'
		@page.should have_text 'CREATE NEW EMAIL'
		@page.should_not have_pagination
		@page.should_not have_bulk_action
		@page.should_not have_action_submit_button
	end

	it 'sorts by subject (asc)' do
		subjects = []

		('A'..'Z').each do |l|
			subjects.push(l)
			@page.generate_data(subject: l, count: 1)
		end
		load_page

		@page.find('th.highlight').text.should eq 'Subject'
		@page.find('th.highlight').should have_css 'a.sort.asc'
		@page.subjects.map {|subject| subject.text}.should == subjects[0..19]
		@page.should have(21).rows # +1 for the header
	end

	it 'sorts by subject (desc)' do
		subjects = []

		('A'..'Z').each do |l|
			subjects.push(l)
			@page.generate_data(subject: l, count: 1)
		end
		subjects.reverse!
		load_page
		@page.subject_header.find('a.sort').click
		no_php_js_errors

		@page.find('th.highlight').text.should eq 'Subject'
		@page.find('th.highlight').should have_css 'a.sort.desc'
		@page.subjects.map {|subject| subject.text}.should == subjects[0..19]
		@page.should have(21).rows # +1 for the header
	end

	it 'sorts by date (asc)' do
		now = Time.now
		dates = []

		(0...25).each do |n|
			my_date = now - (n * 86400)
			dates.push(my_date.to_datetime.strftime("%-m/%-d/%y %-l:%M %p"))
			@page.generate_data(timestamp: my_date.to_i, count: 1)
		end
		dates.reverse!
		load_page
		@page.date_header.find('a.sort').click
		no_php_js_errors

		@page.find('th.highlight').text.should eq 'Date'
		@page.find('th.highlight').should have_css 'a.sort.asc'
		@page.dates.map {|date| date.text}.should == dates[0..19]
		@page.should have(21).rows # +1 for the header
	end

	it 'sorts by date (desc)' do
		now = Time.now
		dates = []

		(0...25).each do |n|
			my_date = now - (n * 86400)
			dates.push(my_date.to_datetime.strftime("%-m/%-d/%y %-l:%M %p"))
			@page.generate_data(timestamp: my_date.to_i, count: 1)
		end
		load_page
		@page.date_header.find('a.sort').click # To sort by date
		no_php_js_errors

		@page.date_header.find('a.sort').click # DESC sort
		no_php_js_errors

		@page.find('th.highlight').text.should eq 'Date'
		@page.find('th.highlight').should have_css 'a.sort.desc'
		@page.dates.map {|date| date.text}.should == dates[0..19]
		@page.should have(21).rows # +1 for the header
	end

	it 'sorts by total sent (asc)' do
		sent = []

		(1..25).each do |n|
			sent.push(n.to_s)
			@page.generate_data(total_sent: n, count: 1)
		end
		load_page
		@page.total_sent_header.find('a.sort').click
		no_php_js_errors

		@page.find('th.highlight').text.should eq 'Total Sent'
		@page.find('th.highlight').should have_css 'a.sort.asc'
		@page.total_sents.map {|sent| sent.text}.should == sent[0..19]
		@page.should have(21).rows # +1 for the header
	end

	it 'sorts by total sent (desc)' do
		sent = []

		(1..25).each do |n|
			sent.push(n.to_s)
			@page.generate_data(total_sent: n, count: 1)
		end
		sent.reverse!
		load_page
		@page.total_sent_header.find('a.sort').click # To sort by total sent
		no_php_js_errors

		@page.total_sent_header.find('a.sort').click # DESC sort
		no_php_js_errors

		@page.find('th.highlight').text.should eq 'Total Sent'
		@page.find('th.highlight').should have_css 'a.sort.desc'
		@page.total_sents.map {|sent| sent.text}.should == sent[0..19]
		@page.should have(21).rows # +1 for the header
	end

	it 'can search by subject' do
		phrase = "Zeppelins"
		data = phrase + " are cool"

		@page.generate_data
		@page.generate_data(count: 5, subject: data)
		load_page

		@page.phrase_search.set phrase
		@page.search_submit_button.click
		no_php_js_errors

		@page.should_not have_no_results
		@page.heading.text.should eq 'Search Results we found 5 results for "' + phrase + '"'
		@page.phrase_search.value.should eq phrase
		@page.should have_text data
		@page.should have(6).rows #+1 for the header
	end

	it 'can search by message' do
		phrase = "Zeppelins"
		data = phrase + " are cool"

		@page.generate_data
		@page.generate_data(count: 5, message: data)
		load_page

		@page.phrase_search.set phrase
		@page.search_submit_button.click
		no_php_js_errors

		@page.should_not have_no_results
		@page.heading.text.should eq 'Search Results we found 5 results for "' + phrase + '"'
		@page.phrase_search.value.should eq phrase
		@page.should have(6).rows #+1 for the header
	end

	it 'can search by from name' do
		phrase = "Zeppelin"
		data = "Ferdinand von Zeppelin"

		@page.generate_data
		@page.generate_data(count: 5, from_name: data)
		load_page

		@page.phrase_search.set phrase
		@page.search_submit_button.click
		no_php_js_errors

		@page.should_not have_no_results
		@page.heading.text.should eq 'Search Results we found 5 results for "' + phrase + '"'
		@page.phrase_search.value.should eq phrase
		@page.should have(6).rows #+1 for the header
	end

	it 'can search by from email' do
		phrase = "zeppelin"
		data = "ferdinand.von.zeppelin@airships.de"

		@page.generate_data
		@page.generate_data(count: 5, from_email: data)
		load_page

		@page.phrase_search.set phrase
		@page.search_submit_button.click
		no_php_js_errors

		@page.should_not have_no_results
		@page.heading.text.should eq 'Search Results we found 5 results for "' + phrase + '"'
		@page.phrase_search.value.should eq phrase
		@page.should have(6).rows #+1 for the header
	end

	it 'can search by recipient' do
		phrase = "zeppelin"
		data = "ferdinand.von.zeppelin@airships.de"

		@page.generate_data
		@page.generate_data(count: 5, recipient: data)
		load_page

		@page.phrase_search.set phrase
		@page.search_submit_button.click
		no_php_js_errors

		@page.should_not have_no_results
		@page.heading.text.should eq 'Search Results we found 5 results for "' + phrase + '"'
		@page.phrase_search.value.should eq phrase
		@page.should have(6).rows #+1 for the header
	end

	it 'can search by cc' do
		phrase = "zeppelin"
		data = "ferdinand.von.zeppelin@airships.de"

		@page.generate_data
		@page.generate_data(count: 5, cc: data)
		load_page

		@page.phrase_search.set phrase
		@page.search_submit_button.click
		no_php_js_errors

		@page.should_not have_no_results
		@page.heading.text.should eq 'Search Results we found 5 results for "' + phrase + '"'
		@page.phrase_search.value.should eq phrase
		@page.should have(6).rows #+1 for the header
	end

	it 'can search by bcc' do
		phrase = "zeppelin"
		data = "ferdinand.von.zeppelin@airships.de"

		@page.generate_data
		@page.generate_data(count: 5, bcc: data)
		load_page

		@page.phrase_search.set phrase
		@page.search_submit_button.click
		no_php_js_errors

		@page.should_not have_no_results
		@page.heading.text.should eq 'Search Results we found 5 results for "' + phrase + '"'
		@page.phrase_search.value.should eq phrase
		@page.should have(6).rows #+1 for the header
	end

	it 'displays "no results" when searching returns nothing' do
		phrase = "Zeppelins"

		@page.generate_data
		load_page

		@page.phrase_search.set phrase
		@page.search_submit_button.click
		no_php_js_errors

		@page.heading.text.should eq 'Search Results we found 0 results for "' + phrase + '"'
		@page.phrase_search.value.should eq phrase

		@page.should have_no_results
		@page.should_not have_bulk_action
		@page.should_not have_action_submit_button
	end

	it 'maintains sort when searching' do
		@page.generate_data

		phrase = "Zeppelins"
		data = phrase + " are cool"

		sent = []

		(1..25).each do |n|
			total_sent = n + Random.rand(42)
			sent.push(total_sent)
			@page.generate_data(subject: data, total_sent: total_sent, count: 1)
		end
		sent.sort!
		load_page
		@page.total_sent_header.find('a.sort').click
		no_php_js_errors

		@page.phrase_search.set phrase
		@page.search_submit_button.click
		no_php_js_errors

		@page.find('th.highlight').text.should eq 'Total Sent'
		@page.find('th.highlight').should have_css 'a.sort.asc'
		@page.should_not have_no_results
		@page.heading.text.should eq 'Search Results we found 20 results for "' + phrase + '"'
		@page.phrase_search.value.should eq phrase
		@page.should have_text data
		@page.total_sents.map {|sent| sent.text}.should == sent[0..19].map {|n| n.to_s}
		@page.should have(21).rows # +1 for the header
	end

	it 'will not pagingate at 25 or under' do
		@page.generate_data(count: 25)
		load_page

		@page.should_not have_pagination
	end

	it 'will paginate at over 26 emails' do
		@page.generate_data(count: 26)
		load_page

		@page.should have_pagination
	    @page.should have(5).pages
	    @page.pages.map {|name| name.text}.should == ["First", "1", "2", "Next", "Last"]
	end

	it 'will show the Prev button when on page 2' do
		@page.generate_data
		load_page

	    click_link "Next"
		no_php_js_errors

		@page.should have_pagination
	    @page.should have(7).pages
	    @page.pages.map {|name| name.text}.should == ["First", "Previous", "1", "2", "3", "Next", "Last"]
	end

	it 'will now show Next on the last page' do
		@page.generate_data
		load_page

	    click_link "Last"
		no_php_js_errors

		@page.should have_pagination
	    @page.should have(6).pages
	    @page.pages.map {|name| name.text}.should == ["First", "Previous", "8", "9", "10", "Last"]
	end

	it 'maintains sort while paging' do
		@page.generate_data
		load_page

		@page.total_sent_header.find('a.sort').click
		no_php_js_errors

	    click_link "Next"
		no_php_js_errors

		@page.find('th.highlight').text.should eq 'Total Sent'
		@page.find('th.highlight').should have_css 'a.sort.asc'
	end

	it 'maintains search while paging' do
		phrase = "Zeppelins"
		data = phrase + " are cool"
		@page.generate_data(subject: "Albatross")
		@page.generate_data(subject: data)
		load_page

		@page.phrase_search.set phrase
		@page.search_submit_button.click
		no_php_js_errors

	    click_link "Next"
		no_php_js_errors

		@page.should_not have_no_results
		@page.heading.text.should eq 'Search Results we found 20 results for "' + phrase + '"'
		@page.phrase_search.value.should eq phrase
		@page.should have_text data
		@page.should_not have_text "Albatross"
	end

	it 'maintains sort and search while paging' do
		phrase = "Zeppelins"
		data = phrase + " are cool"
		@page.generate_data(subject: "Albatross")
		@page.generate_data(subject: data)
		load_page

		@page.phrase_search.set phrase
		@page.search_submit_button.click
		no_php_js_errors

		@page.total_sent_header.find('a.sort').click
		no_php_js_errors

	    click_link "Next"
		no_php_js_errors

		@page.should_not have_no_results
		@page.heading.text.should eq 'Search Results we found 20 results for "' + phrase + '"'
		@page.phrase_search.value.should eq phrase
		@page.should have_text data
		@page.should_not have_text "Albatross"
		@page.find('th.highlight').text.should eq 'Total Sent'
		@page.find('th.highlight').should have_css 'a.sort.asc'
	end

	it 'resets the page on a new sort' do
		@page.generate_data
		load_page

		@page.should have_pagination
	    @page.should have(6).pages
	    @page.pages.map {|name| name.text}.should == ["First", "1", "2", "3", "Next", "Last"]
		@page.pagination.find('a.act').text.should eq '1'

		click_link "Next"
		no_php_js_errors

	    @page.pages.map {|name| name.text}.should == ["First", "Previous", "1", "2", "3", "Next", "Last"]
		@page.pagination.find('a.act').text.should eq '2'

		@page.total_sent_header.find('a.sort').click
		no_php_js_errors

	    @page.pages.map {|name| name.text}.should == ["First", "1", "2", "3", "Next", "Last"]
		@page.pagination.find('a.act').text.should eq '1'
	end

	it 'resets the page on a new search' do
		phrase = "Zeppelins"
		data = phrase + " are cool"

		@page.generate_data(subject: data)
		load_page

		@page.should have_pagination
	    @page.should have(6).pages
	    @page.pages.map {|name| name.text}.should == ["First", "1", "2", "3", "Next", "Last"]
		@page.pagination.find('a.act').text.should eq '1'

		click_link "Next"
		no_php_js_errors

	    @page.pages.map {|name| name.text}.should == ["First", "Previous", "1", "2", "3", "Next", "Last"]
		@page.pagination.find('a.act').text.should eq '2'

		@page.phrase_search.set phrase
		@page.search_submit_button.click
		no_php_js_errors

	    @page.pages.map {|name| name.text}.should == ["First", "1", "2", "3", "Next", "Last"]
		@page.pagination.find('a.act').text.should eq '1'
	end

	it 'can view an email' do
		my_subject = 'Rspec utilities/communicate test plain text email'
		my_body = "This a test email sent from the communicate tool."
		test_from = 'ellislab.developers.rspec@mailinator.com'
		email = 'ferdinand.von.zeppelin@airships.de'

		@page.generate_data(subject: my_subject, from_email: test_from, recipient: email, cc: email, bcc: email, message: my_body, count: 1)
		load_page

		@page.first('ul.toolbar li.view a').click
		no_php_js_errors

		@page.should have_modal
		@page.modal_title.text.should eq my_subject
		@page.modal.text.should include my_body
	end

	it 'can resend an email' do
		my_subject = 'Rspec utilities/communicate test plain text email'
		my_body = "This a test email sent from the communicate tool."
		test_from = 'ellislab.developers.rspec@mailinator.com'
		email = 'ferdinand.von.zeppelin@airships.de'

		@page.generate_data(subject: my_subject, from_email: test_from, recipient: email, cc: email, bcc: email, message: my_body, count: 1)
		load_page

		@page.first('ul.toolbar li.sync a').click
		no_php_js_errors

		communicate = Communicate.new

		communicate.should be_displayed
		communicate.heading.text.should eq 'Communicate Required Fields'

		communicate.subject.value.should eq my_subject
		communicate.from_email.value.should eq test_from
		communicate.recipient.value.should eq email
		communicate.cc.value.should eq email
		communicate.bcc.value.should eq email
		communicate.body.value.should eq my_body
	end

	it 'displays an itemized confirmation modal when removing 5 or less emails' do
		phrase = "Zeppelins"
		data = phrase + " are cool"

		@page.generate_data(count: 5, subject: data)
		@page.generate_data(count: 12)
		load_page

		@page.rows.each do |row|
			if row.text.include? data
				row.find('input[type="checkbox"]').set true
			end
		end

		@page.wait_until_bulk_action_visible
		@page.bulk_action.select "Remove"
		@page.action_submit_button.click

		@page.wait_until_modal_visible
		@page.modal_title.text.should eq "Confirm Removal"
		@page.modal.text.should include "You are attempting to remove the following items, please confirm this action."
		@page.modal.text.should include data
		@page.modal.all('.checklist li').length.should eq 5
	end

	it 'displays a bulk confirmation modal when removing more than 5 emails' do
		phrase = "Zeppelins"
		data = phrase + " are cool"

		@page.generate_data(count: 6, subject: data)
		@page.generate_data(count: 12)
		load_page

		@page.rows.each do |row|
			if row.text.include? data
				row.find('input[type="checkbox"]').set true
			end
		end

		@page.wait_until_bulk_action_visible
		@page.bulk_action.select "Remove"
		@page.action_submit_button.click

		@page.wait_until_modal_visible
		@page.modal_title.text.should eq "Confirm Removal"
		@page.modal.text.should include "You are attempting to remove the following items, please confirm this action."
		@page.modal.text.should include "Sent Emails: 6 Emails"
	end

	it 'can remove emails in bulk' do
		phrase = "Zeppelins"
		data = phrase + " are cool"

		@page.generate_data(count: 8, subject: data)
		@page.generate_data(count: 12)
		load_page

		@page.rows.each do |row|
			if row.text.include? data
				row.find('input[type="checkbox"]').set true
			end
		end

		@page.wait_until_bulk_action_visible
		@page.bulk_action.select "Remove"
		@page.action_submit_button.click
		@page.wait_until_modal_visible
		@page.modal_submit_button.click # Submits a form
		no_php_js_errors

		@page.should have(13).rows # +1 for the header
		@page.should_not have_text data
	end

end