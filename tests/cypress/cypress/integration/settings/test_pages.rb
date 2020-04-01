require './bootstrap.rb'

context('Pages Settings', () => {

  beforeEach(function() {
    cy.auth();

    page = PagesSettings.new
    page.settings_btn.click()
    page.get('wrap').invoke('text').then((text) => {
			expect(text).not.contains( 'Pages Settings'

    // Install Pages
    addon_manager = AddonManager.new
    addon_manager.load
    cy.hasNoErrors()
    addon_manager.first_party_addons.each do |addon|
      if addon.text.include? 'Pages'
        addon.find('li.txt-only a.add').click()
        break
      }
    }

    page = PagesSettings.new
    page.load()
    cy.hasNoErrors()
  }

  it('should show the Pages Settings screen', () => {
    page.should have_homepage_display
    page.should have_default_channel
    page.should have_channel_default_template

    page.all_there?.should == true

    page.homepage_display.has_checked_radio('not_nested').should == true

    page.default_channel.has_checked_radio('0').should == true
    page.channel_default_template[0].value.should == '0'
    page.channel_default_template[1].value.should == '0'
  }

  it('should save new Pages settings', () => {
    page.homepage_display.choose_radio_option('nested')
    page.default_channel.choose_radio_option('1')
    page.channel_default_template[0].select('about/404'
    page.channel_default_template[1].select('news/index'
    page.submit

    cy.hasNoErrors()
    page.get('wrap').contains('Preferences updated'
    page.homepage_display.has_checked_radio('nested').should == true
    page.default_channel.has_checked_radio('1').should == true
    page.channel_default_template[0].value.should == '2'
    page.channel_default_template[1].value.should == '10'
  }
}
