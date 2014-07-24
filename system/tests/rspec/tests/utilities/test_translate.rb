require './bootstrap.rb'

feature 'Translate Tool' do
	path = '../../expressionengine/language/'

	before(:all) do
		FileUtils.mkdir(path + 'rspeclingo')
		FileUtils.cp_r(Dir.glob(path + 'english/*'), path + 'rspeclingo/')
	end

	before(:each) do
		cp_session
		@page = Translate.new
		@page.load

		@page.displayed?
		@page.title.text.should eq 'English Language Files'
		@page.should have_phrase_search
		@page.should have_search_submit_button
		@page.should have_bulk_action
		@page.should have_action_submit_button
	end

	after(:all) do
		FileUtils.remove_dir(path + 'rspeclingo/', true)
	end

	it 'displays 2 languages in the sidebar' do
		@page.should have(2).languages
		@page.languages.map {|lang| lang.text}.should == ["English (default)", 'Rspeclingo']
	end

	it 'displays the default language first in the sidebar' do
		ee_config(item: 'deft_lang', value: 'rspeclingo')
		@page.load
		@page.languages.map {|lang| lang.text}.should == ["Rspeclingo (default)", 'English']
		ee_config(item: 'deft_lang', value: 'english')
	end

	it 'shows the English Language files' do
		@page.should have_pagination
		@page.should have(5).pages
		@page.pages.map {|name| name.text}.should == ["First", "1", "2", "Next", "Last"]

		@page.should have(51).rows # 50 rows per page + header row
	end

	it 'can search by phrases' do
		my_phrase = 'admin'
		@page.should have_text my_phrase
		@page.phrase_search.set my_phrase
		@page.search_submit_button.click

		@page.phrase_search.value.should eq my_phrase
		@page.should have_text my_phrase
		@page.should have(3).rows # 2 rows + header row
		@page.should_not have_pagination
	end

	it 'reports "no results" when a search fails' do
		my_phrase = 'foobarbaz'
		@page.should_not have_text my_phrase
		@page.phrase_search.set my_phrase
		@page.search_submit_button.click

		@page.phrase_search.value.should eq my_phrase
		@page.should_not have_pagination
		@page.should have_no_results
	end

	it 'paginates' do
		click_link "Next"

		@page.should have_pagination
		@page.should have(5).pages
		@page.pages.map {|name| name.text}.should == ["First", "Previous", "1", "2", "Last"]

		@page.should have(8).rows # 7 rows + header row
	end

	it 'sorts by file name' do
		@page.find('a.sort').click
		@page.should have_css('a.desc')
		@page.should_not have_css('a.asc')
	end

	it 'keeps sort while paginating' do
		@page.find('a.sort').click
		click_link "Next"

		@page.should have_css('a.desc')
		@page.should_not have_css('a.asc')
	end

	# The capybara/webkit driver is munging headers.
	# it 'can export language files' do
	# 	@page.find('input[type="checkbox"][title="select all"]').set(true)
	# 	@page.bulk_action.select "Export (Download)"
	# 	@page.action_submit_button.click
	# 	@page.response_headers['Content-Disposition'].should include 'attachment; filename='
	# end

	it 'shows an error if nothing is selected when exporting' do
		@page.bulk_action.select "Export (Download)"
		@page.action_submit_button.click
		@page.should have_alert
	end

	it 'shows an error if any of the selected files is not readable' do
		FileUtils.chmod 0000, path + 'rspeclingo/admin_lang.php'

		click_link "Rspeclingo"
		@page.find('input[type="checkbox"][title="select all"]').set(true)
		@page.bulk_action.select "Export (Download)"
		@page.action_submit_button.click
		@page.should have_alert

		FileUtils.chmod 0755, path + 'rspeclingo/admin_lang.php'
	end

	# Not sure how to force this error
	# it 'shows an error if a ZipArchive cannot be created' do
	# 	@page.find('input[type="checkbox"][title="select all"]').set(true)
	# 	@page.bulk_action.select "Export (Download)"
	# 	@page.action_submit_button.click
	# end

	it 'uses the default language when language is not specified in the URL' do
		new_url = @page.current_url.gsub('/english', '')

		visit(new_url)

		@page.title.text.should eq 'English Language Files'
	end

	it 'can use multiple languages' do
		click_link "Rspeclingo"
		@page.title.text.should eq 'Rspeclingo Language Files'
		@page.should have_pagination
		@page.should have(5).pages
		@page.pages.map {|name| name.text}.should == ["First", "1", "2", "Next", "Last"]

		@page.should have(51).rows # 50 rows per page + header row
	end

	it 'returns a 404 for an invalid language' do
		new_url = @page.current_url.gsub('english', 'gibberish')

		visit(new_url)

		@page.should have_text "404 Page Not Found"
	end
end