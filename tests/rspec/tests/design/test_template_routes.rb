require './bootstrap.rb'

feature 'Template Routes' do
  before(:each) do
    cp_session
    @page = TemplateRoutes.new
    @page.load
    no_php_js_errors
  end

  it 'displays' do
    @page.should have_new_route_button
    @page.should have_update_button

    @page.wait_until_no_results_visible
    @page.should have_no_results
  end
end
