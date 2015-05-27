require './bootstrap.rb'

feature 'Pages Settings' do

	before(:each) do
		cp_session

		@page = PagesSettings.new
		@page.settings_btn.click
		@page.should have_no_text 'Pages Settings'

		# Install Pages
		addon_manager = AddonManager.new
		addon_manager.load
		no_php_js_errors
		click_link '2'
		addon_manager.addons.each do |addon|
			if addon.text.include? 'Pages'
				addon.find('li.install a').click
				break
			end
		end

		@page = PagesSettings.new
		@page.load
		no_php_js_errors
	end

	it 'should show the Pages Settings screen' do
		@page.all_there?.should == true

		@page.nested.checked?.should == false
		@page.not_nested.checked?.should == true
		@page.default_channel.value.should == '0'
		@page.channel_default_template[0].value.should == '0'
		@page.channel_default_template[1].value.should == '0'
	end

	it 'should save new Pages settings' do
		@page.nested.click
		@page.default_channel.select 'News'
		@page.channel_default_template[0].select 'about/404'
		@page.channel_default_template[1].select 'news/index'
		@page.submit

		no_php_js_errors
		@page.should have_text 'Preferences updated'
		@page.nested.checked?.should == true
		@page.not_nested.checked?.should == false
		@page.default_channel.value.should == '1'
		@page.channel_default_template[0].value.should == '2'
		@page.channel_default_template[1].value.should == '10'
	end
end