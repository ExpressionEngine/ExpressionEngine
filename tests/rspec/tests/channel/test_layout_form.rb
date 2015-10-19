require './bootstrap.rb'

feature 'Channel Layouts: Create/Edit' do
	before(:each) do
		cp_session
		@page = ChannelLayoutForm.new
		@page.load
		no_php_js_errors
	end

	it 'display the Create Form Layout view' do
		@page.should have_breadcrumb
		@page.should have_sidebar
		@page.should have_heading
		@page.should have_add_tab_button
		@page.should have_tabs
		@page.should have_publish_tab
		@page.should have_date_tab
		@page.should have_hide_date_tab
		@page.should have_categories_tab
		@page.should have_hide_categories_tab
		@page.should have_options_tab
		@page.should have_hide_options_tab
		@page.should have_layout_name
		@page.should have_member_groups
		@page.should have_submit_button
	end

	# Bug #21191
	context 'Hiding the Options Tab' do
		it 'should still be hidden with an invalid form' do
		end

		it 'should be hidden when saved' do
		end
	end

	# Bug #21191
	context 'Hiding fields in the Options Tab' do
		it 'should still be hidden with an invalid form' do
		end

		it 'should be hidden when saved' do
		end
	end

	# Bug #21191
	it 'can move a field out the Options tab' do
	end

end
