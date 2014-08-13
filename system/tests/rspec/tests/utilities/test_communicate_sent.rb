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

		@page.should be_displayed
		@page.title.text.should eq 'Sent e-mails'
		@page.should have_phrase_search
		@page.should have_search_submit_button
		@page.should have_email_table
		@page.should have_bulk_action
		@page.should have_action_submit_button
	end

	it 'shows the sent e-mails page (with no results)' do
		@page.load

		@page.should be_displayed
		@page.title.text.should eq 'Sent e-mails'
		@page.should have_phrase_search
		@page.should have_search_submit_button
		@page.should have_email_table
		@page.should have_no_results
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
		@page.subjects.map {|subject| subject.text}.should == subjects
		@page.should have(27).rows # +1 for the header
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

		@page.find('th.highlight').text.should eq 'Subject'
		@page.find('th.highlight').should have_css 'a.sort.desc'
		@page.subjects.map {|subject| subject.text}.should == subjects
		@page.should have(27).rows # +1 for the header
	end

	it 'sorts by date (asc)' do
		now = DateTime.now
		dates = []

		(0...25).each do |n|
			my_date = now - n
			dates.push(my_date.strftime("%-m/%-d/%y %-l:%M %p"))
			@page.generate_data(timestamp: my_date.to_time.to_i, count: 1)
		end
		dates.reverse!
		load_page
		@page.date_header.find('a.sort').click

		@page.find('th.highlight').text.should eq 'Date'
		@page.find('th.highlight').should have_css 'a.sort.asc'
		@page.dates.map {|date| date.text}.should == dates
		@page.should have(26).rows # +1 for the header
	end

	it 'sorts by date (desc)' do
		now = DateTime.now
		dates = []

		(0...25).each do |n|
			my_date = now - n
			dates.push(my_date.strftime("%-m/%-d/%y %-l:%M %p"))
			@page.generate_data(timestamp: my_date.to_time.to_i, count: 1)
		end
		load_page
		@page.date_header.find('a.sort').click # To sort by date
		@page.date_header.find('a.sort').click # DESC sort

		@page.find('th.highlight').text.should eq 'Date'
		@page.find('th.highlight').should have_css 'a.sort.desc'
		@page.dates.map {|date| date.text}.should == dates
		@page.should have(26).rows # +1 for the header
	end

	it 'sorts by total sent (asc)' do
		sent = []

		(1..25).each do |n|
			sent.push(n.to_s)
			@page.generate_data(total_sent: n, count: 1)
		end
		load_page
		@page.total_sent_header.find('a.sort').click

		@page.find('th.highlight').text.should eq 'Total Sent'
		@page.find('th.highlight').should have_css 'a.sort.asc'
		@page.total_sents.map {|sent| sent.text}.should == sent
		@page.should have(26).rows # +1 for the header
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
		@page.total_sent_header.find('a.sort').click # DESC sort

		@page.find('th.highlight').text.should eq 'Total Sent'
		@page.find('th.highlight').should have_css 'a.sort.desc'
		@page.total_sents.map {|sent| sent.text}.should == sent
		@page.should have(26).rows # +1 for the header
	end

	it 'can search by subject' do
		phrase = "Zeppelins"
		data = phrase + " are cool"

		@page.generate_data
		@page.generate_data(count: 5, subject: data)
		load_page

		@page.phrase_search.set phrase
		@page.search_submit_button.click

		@page.should_not have_no_results
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

		@page.should_not have_no_results
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

		@page.should_not have_no_results
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

		@page.should_not have_no_results
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

		@page.should_not have_no_results
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

		@page.should_not have_no_results
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

		@page.should_not have_no_results
		@page.phrase_search.value.should eq phrase
		@page.should have(6).rows #+1 for the header
	end

	it 'displays "no results" when searching returns nothing' do
	end

	it 'maintains sort when searching' do
	end

	it 'will not pagingate at 50 or under' do
	end

	it 'will paginate at over 50 emails' do
	end

	it 'maintains sort while paging' do
	end

	it 'maintains search while paging' do
	end

	it 'maintains sort and search while paging' do
	end

	it 'resets the page on a new sort' do
	end

	it 'resets the page on a new search' do
	end

	it 'can view an email' do
	end

	it 'can resend an email' do
	end

	it 'can remove emails in bulk' do
	end

end