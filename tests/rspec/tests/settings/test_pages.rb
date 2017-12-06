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
    addon_manager.first_party_addons.each do |addon|
      if addon.text.include? 'Pages'
        addon.find('li.txt-only a.add').click
        break
      end
    end

    @page = PagesSettings.new
    @page.load
    no_php_js_errors
  end

  it 'should show the Pages Settings screen' do
    @page.should have_homepage_display
    @page.should have_default_channel
    @page.should have_channel_default_template

    @page.all_there?.should == true

    @page.homepage_display.has_checked_radio('not_nested').should == true

    @page.default_channel.has_checked_radio('0').should == true
    @page.channel_default_template[0].value.should == '0'
    @page.channel_default_template[1].value.should == '0'
  end

  it 'should save new Pages settings' do
    @page.homepage_display.choose_radio_option('nested')
    @page.default_channel.choose_radio_option('1')
    @page.channel_default_template[0].select 'about/404'
    @page.channel_default_template[1].select 'news/index'
    @page.submit

    no_php_js_errors
    @page.should have_text 'Preferences updated'
    @page.homepage_display.has_checked_radio('nested').should == true
    @page.default_channel.has_checked_radio('1').should == true
    @page.channel_default_template[0].value.should == '2'
    @page.channel_default_template[1].value.should == '10'
  end
end
