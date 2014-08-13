require './bootstrap.rb'

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
		load_page
		@page.should have_no_results
		@page.should_not have_pagination
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
	end

	it 'sorts by date (desc)' do
	end

	it 'sorts by total sent (asc)' do
	end

	it 'sorts by total sent (desc)' do
	end

	it 'can search' do
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