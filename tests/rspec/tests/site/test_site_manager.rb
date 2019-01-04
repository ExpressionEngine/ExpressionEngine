require './bootstrap.rb'

feature 'Site Manager' do
  before(:each) do
    skip 'Need a license in order to test MSM stuff' do
    end
    ee_config(item: 'multiple_sites_enabled', value: 'y')
    cp_session
    @page = SiteManager.new
    @page.load
    no_php_js_errors
  end

  it 'displays' do
    @page.all_there?.should == true
  end

  context 'with multiple sites' do
    before(:each) do
      @page.add_site_button.click
      no_php_js_errors

      @form = SiteForm.new
      @form.add_site(
        name: 'Rspec Site',
        short_name: 'rspec_site',
      )

      no_php_js_errors

      @page.should have_alert
      @page.alert.text.should include 'Site Created'
      @page.alert.text.should include 'Rspec Site'
    end

    it 'can add a site' do
      @page.sites.should have(2).items
      @page.sites[1].id.text.should eq '2'
      @page.sites[1].name.text.should eq 'Rspec Site'
      @page.sites[1].short_name.text.should eq '{rspec_site}'
      @page.sites[1].status.text.should eq 'ONLINE'
    end

    it 'can delete a site' do
      @page.sites[1].bulk_action_checkbox.click
      @page.wait_for_bulk_action

      @page.has_bulk_action?.should == true
      @page.has_action_submit_button?.should == true

      @page.bulk_action.select 'Remove'
      @page.action_submit_button.click

      @page.wait_for_modal_submit_button
      @page.modal_submit_button.click

      no_php_js_errors

      @page.should have_alert
      @page.should have_alert_success
      @page.sites.should have(1).items
    end

    it 'can switch sites' do
      @page.find('.nav-sites a.nav-has-sub').click
      @page.find('a[href*="cp/msm/switch_to/2"]').click

      no_php_js_errors

      @page.find('.nav-sites a.nav-has-sub').text.should eq 'Rspec Site'
    end
  end
end
