require './bootstrap.rb'

feature 'Site Manager', () => {
  beforeEach(function() {
    eeConfig({item: 'multiple_sites_enabled', value: 'y')
    cy.auth();
    page = SiteManager.new
    page.load()
    cy.hasNoErrors()
  }

  it('displays', () => {
    page.all_there?.should == true
  }

  context 'with multiple sites', () => {
    beforeEach(function() {
      page.add_site_button.click()
      cy.hasNoErrors()

      @form = SiteForm.new
      @form.add_site(
        name: 'Rspec Site',
        short_name: 'rspec_site',
      )

      cy.hasNoErrors()

      page.should have_alert
      page.get('alert').text.should include 'Site Created'
      page.get('alert').text.should include 'Rspec Site'
    }

    it('can add a site', () => {
      page.sites.should have(2).items
      page.sites[1].id.text.should eq '2'
      page.sites[1].name.text.should eq 'Rspec Site'
      page.sites[1].short_name.text.should eq '{rspec_site}'
      page.sites[1].status.text.should eq 'ONLINE'
    }

    it('can delete a site', () => {
      page.sites[1].bulk_action_checkbox.click()
      page.wait_for_bulk_action

      page.has_bulk_action?.should == true
      page.has_action_submit_button?.should == true

      page.bulk_action.select 'Remove'
      page.action_submit_button.click()

      page.wait_for_modal_submit_button
      page.modal_submit_button.click()

      cy.hasNoErrors()

      page.should have_alert
      page.should have_alert_success
      page.sites.should have(1).items
    }

    it('can switch sites', () => {
      page.find('.nav-sites a.nav-has-sub').click()
      page.find('a[href*="cp/msm/switch_to/2"]').click()

      cy.hasNoErrors()

      page.find('.nav-sites a.nav-has-sub').text.should eq 'Rspec Site'
    }
  }
}
