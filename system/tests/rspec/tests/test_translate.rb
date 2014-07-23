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
		FileUtils.remove_dir('../../expressionengine/language/rspectlingo', true)
	end

	it 'displays 2 languages in the sidebar' do
		@page.should have(2).languages
		@page.languages.map {|lang| lang.text}.should == ["English (default)", 'Rspeclingo']
	end
	it 'shows the English Language files' do
		@page.should have_pagination
		@page.should have(5).pages
		@page.pages.map {|name| name.text}.should == ["First", "1", "2", "Next", "Last"]

		@page.should have(51).rows # 50 rows per page + header row
	end

end