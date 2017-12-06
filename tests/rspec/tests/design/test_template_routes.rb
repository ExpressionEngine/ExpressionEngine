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

  it 'can add a new route' do
    @page.add_route(
      template: '1',
      route: 'foo/bar'
    )
    @page.update_button.click

    no_php_js_errors

    @page.should have_alert
    @page.alert.text.should include 'Template Routes Saved'
    @page.routes.should have(1).items
    @page.routes[0].template.text.should eq 'index'
    @page.routes[0].group.text.should eq 'about'
    @page.routes[0].route.value.should eq 'foo/bar'
    @page.routes[0].segments_required[:class].should include 'off'
  end

  it 'can edit a route' do
    @page.add_route(
      template: '1',
      route: 'foo/bar'
    )
    @page.update_button.click

    @page.routes[0].route.set 'rspec/edited'

    @page.update_button.click

    @page.routes[0].route.value.should eq 'rspec/edited'
  end

  it 'can reorder routes' do
    @page.add_route(
      template: '1',
      route: 'foo/bar'
    )
    @page.add_route(
      template: '2',
      route: 'boo/far'
    )
    @page.update_button.click

    @page.routes.should have(2).items

    @page.routes[0].route.value.should eq 'foo/bar'
    @page.routes[1].route.value.should eq 'boo/far'

    @page.routes[1].reorder.drag_to(@page.routes[0].reorder)

    @page.update_button.click

    @page.routes[0].route.value.should eq 'boo/far'
    @page.routes[1].route.value.should eq 'foo/bar'
  end

  it 'can remove a route' do
    @page.add_route(
      template: '1',
      route: 'foo/bar'
    )
    @page.add_route(
      template: '2',
      route: 'boo/far'
    )
    @page.update_button.click

    @page.routes.should have(2).items

    @page.routes[0].delete.click
    @page.update_button.click
    @page.routes.should have(1).items
    @page.routes[0].route.value.should eq 'boo/far'
  end
end
