require './bootstrap.rb'

feature 'Translate Tool' do
	english_path = '../../expressionengine/language/english/'
	language_path = '../../language/'
	translations_path = '../../expressionengine/translations/'

	before(:all) do
		FileUtils.mkdir(language_path + 'rspeclingo')
		FileUtils.cp_r(Dir.glob(english_path + '*'), language_path + 'rspeclingo/')
	end

	before(:each, :edit => false) do
		cp_session
		@list_page = Translate.new
		@edit_page = TranslateEdit.new

		@list_page.load

		@list_page.should be_displayed
		@edit_page.should_not be_displayed

		@list_page.heading.text.should eq 'English Language Files'
		@list_page.should have_phrase_search
		@list_page.should have_search_submit_button
		@list_page.should have_bulk_action
		@list_page.should have_action_submit_button
	end

	before(:each, :edit => true) do
		cp_session
		@list_page = Translate.new
		@edit_page = TranslateEdit.new

		@edit_page.load

		@edit_page.should be_displayed

		@edit_page.heading.text.should eq 'addons_lang.php Translation'
		@edit_page.should have_breadcrumb
		@edit_page.should have_items
		@edit_page.should have_submit_button
		@edit_page.breadcrumb.text.should include 'English Language Files'
	end

	after(:all) do
		FileUtils.remove_dir(language_path + 'rspeclingo/', true)
		FileUtils.rm Dir.glob(translations_path + '*.php')
	end

	it 'displays 2 languages in the sidebar', :edit => false do
		@list_page.should have(2).languages
		@list_page.languages.map {|lang| lang.text}.should == ["English (default)", 'Rspeclingo']
	end

	it 'displays the default language first in the sidebar', :edit => false do
		ee_config(item: 'deft_lang', value: 'rspeclingo')
		@list_page.load
		@list_page.languages.map {|lang| lang.text}.should == ["Rspeclingo (default)", 'English']
		ee_config(item: 'deft_lang', value: 'english')
	end

	it 'shows the English Language files', :edit => false do
		@list_page.should have_pagination
		@list_page.should have(6).pages
		@list_page.pages.map {|name| name.text}.should == ["First", "1", "2", "3", "Next", "Last"]

		@list_page.should have(21).rows # 20 rows per page + header row
	end

	it 'can search by phrases', :edit => false do
		my_phrase = 'admin'
		@list_page.should have_text my_phrase
		@list_page.phrase_search.set my_phrase
		@list_page.search_submit_button.click
		no_php_js_errors

		@list_page.heading.text.should eq 'Search Results we found 2 results for "' + my_phrase + '"'
		@list_page.phrase_search.value.should eq my_phrase
		@list_page.should have_text my_phrase
		@list_page.should have(3).rows # 2 rows + header row
		@list_page.should_not have_pagination
	end

	it 'reports "no results" when a search fails', :edit => false do
		my_phrase = 'foobarbaz'
		@list_page.should_not have_text my_phrase
		@list_page.phrase_search.set my_phrase
		@list_page.search_submit_button.click
		no_php_js_errors

		@list_page.heading.text.should eq 'Search Results we found 0 results for "' + my_phrase + '"'
		@list_page.phrase_search.value.should eq my_phrase
		@list_page.should_not have_pagination
		@list_page.should have_no_results
	end

	it 'paginates', :edit => false do
		click_link "Next"
		no_php_js_errors

		@list_page.should have_pagination
		@list_page.should have(7).pages
		@list_page.pages.map {|name| name.text}.should == ["First", "Previous", "1", "2", "3", "Next", "Last"]

		files = Dir.glob(english_path + '*_lang.php')
		files = files[20..39]
		@list_page.should have(files.count + 1).rows # +1 for header row
	end

	it 'sorts by file name', :edit => false do
		@list_page.find('a.sort').click
		no_php_js_errors

		@list_page.should have_css('a.desc')
		@list_page.should_not have_css('a.asc')
	end

	it 'keeps sort while paginating', :edit => false do
		@list_page.find('a.sort').click
		no_php_js_errors

		click_link "Next"
		no_php_js_errors

		@list_page.should have_css('a.desc')
		@list_page.should_not have_css('a.asc')
	end

	# The capybara/webkit driver is munging headers.
	# it 'can export language files', :edit => false do
	# 	@list_page.find('input[type="checkbox"][title="select all"]').set(true)
	# 	@list_page.bulk_action.select "Export (Download)"
	# 	@list_page.action_submit_button.click
	# 	@list_page.response_headers['Content-Disposition'].should include 'attachment; filename='
	# end

	it 'shows an error if nothing is selected when exporting', :edit => false do
		@list_page.bulk_action.select "Export (Download)"
		@list_page.action_submit_button.click
		no_php_js_errors

		@list_page.should have_alert
	end

	it 'shows an error if any of the selected files is not readable', :edit => false do
		FileUtils.chmod 0000, language_path + 'rspeclingo/admin_lang.php'

		click_link "Rspeclingo"
		no_php_js_errors

		@list_page.find('input[type="checkbox"][title="select all"]').set(true)
		@list_page.bulk_action.select "Export (Download)"
		@list_page.action_submit_button.click
		no_php_js_errors

		@list_page.should have_alert

		FileUtils.chmod 0644, language_path + 'rspeclingo/admin_lang.php'
	end

	# Not sure how to force this error
	# it 'shows an error if a ZipArchive cannot be created', :edit => false do
	# 	@list_page.find('input[type="checkbox"][title="select all"]').set(true)
	# 	@list_page.bulk_action.select "Export (Download)"
	# 	@list_page.action_submit_button.click
	# end

	it 'uses the default language when language is not specified in the URL', :edit => false do
		new_url = @list_page.current_url.gsub('/english', '')

		visit(new_url)

		@list_page.heading.text.should eq 'English Language Files'
	end

	it 'can use multiple languages', :edit => false do
		click_link "Rspeclingo"
		no_php_js_errors

		@list_page.heading.text.should eq 'Rspeclingo Language Files'
		@list_page.should have_pagination
		@list_page.should have(6).pages
		@list_page.pages.map {|name| name.text}.should == ["First", "1", "2", "3", "Next", "Last"]

		@list_page.should have(21).rows # 20 rows per page + header row
	end

	it 'returns a 404 for an invalid language', :edit => false do
		new_url = @list_page.current_url.gsub('english', 'gibberish')

		visit(new_url)

		@list_page.should have_text "404 Page Not Found"
	end

	it 'shows a breadcrumb link on the edit page', :edit => true do
		list_page = Translate.new

		@edit_page.breadcrumb.find('a').click
		no_php_js_errors

		@list_page.should be_displayed
		@edit_page.should_not be_displayed
	end

	it 'displays an error when it cannot create a new translation file', :edit => true do
		t_stat = File::Stat.new(translations_path)
		FileUtils.chmod 0000, translations_path

		@edit_page.items[1].find('input').set('Rspeced!')
		@edit_page.submit_button.click
		no_php_js_errors

		@edit_page.should have_alert
		@edit_page.should have_css('div.alert.issue')

		FileUtils.chmod t_stat.mode, translations_path
	end

	it 'displays a flash message after saving a translation', :edit => true do
		@edit_page.items[1].find('input').set('Rspeced!')
		@edit_page.submit_button.click
		no_php_js_errors

		@edit_page.should have_alert
		@edit_page.should have_css('div.alert.success')
		File.exists?(translations_path + 'addons_lang.php')
	end

	it 'displays an error when it cannot write to the translations directory (update a translation)', :edit => true do
		t_stat = File::Stat.new(translations_path)
		FileUtils.chmod 0000, translations_path

		@edit_page.items[1].find('input').set('Rspeced!')
		@edit_page.submit_button.click
		no_php_js_errors

		@edit_page.should have_alert
		@edit_page.should have_css('div.alert.issue')

		FileUtils.chmod t_stat.mode, translations_path
	end

	it 'displays an error when trying to edit a file that is not readable', :edit => true do
		# Off the rspeclingo language
		new_url = @list_page.current_url.gsub('english', 'rspeclingo')
		visit(new_url)

		FileUtils.chmod 0000, language_path + 'rspeclingo/addons_lang.php'

		@edit_page.items[1].find('input').set('Rspeced!')
		@edit_page.submit_button.click
		no_php_js_errors

		@edit_page.should have_alert
		@edit_page.should have_css('div.alert.issue')

		FileUtils.chmod 0644, language_path + 'rspeclingo/addons_lang.php'
	end
end