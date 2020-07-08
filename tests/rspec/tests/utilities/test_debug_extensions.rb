require './bootstrap.rb'

feature 'Debug Extensions' do

  before(:each) do
    cp_session

    @page = DebugExtensions.new
    @page.load

    @page.displayed?
    @page.heading.text.should eq 'Manage Add-on Extensions'

    @page.should have_addons
  end
  #moved over --Brad
  it 'shows the Manage Add-on Extensions page' do
    @page.addon_name_header[:class].should eq 'highlight'
    @page.should have(2).addons # RTE + Header
  end

  # it 'can sort by name'
  # it 'can sort by status'
  #moved --Brad
  it 'can disable and enable an extension' do
    @page.statuses[0].text.should eq 'ENABLED'

    # Disable an add-on
    @page.checkbox_header.find('input[type="checkbox"]').set true


    @page.wait_until_bulk_action_visible
    @page.bulk_action.select "Disable"


    @page.action_submit_button.click
    no_php_js_errors

    @page.statuses[0].text.should eq 'DISABLED'

    # Enable an add-on
    @page.checkbox_header.find('input[type="checkbox"]').set true
    @page.wait_until_bulk_action_visible
    @page.bulk_action.select "Enable"
    @page.action_submit_button.click
    no_php_js_errors

    @page.statuses[0].text.should eq 'ENABLED'
  end


  #moved --Brad
  it 'can navigate to a manual page' do
    @page.find('ul.toolbar li.manual a').click
  end

end
