require './bootstrap.rb'

feature 'Translate Tool' do

	before(:all) do
		path = '../../expressionengine/language/'
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
		FileUtils.remove_dir('../../expressionengine/language/rspeclingo', true)
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
	end

	it 'can export language files' do
	end

	it 'uses the default language when language is not specified in the URL' do
	end

	it 'returns a 404 for an invalid language' do
	end
end