require './bootstrap.rb'

feature 'Channel Layouts: Create/Edit' do
	before(:each) do
		cp_session
		@page = ChannelLayoutForm.new
		@page.load
		no_php_js_errors
		@page.displayed?
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
		@page.should have_layout_name
		@page.should have_member_groups
		@page.should have_submit_button
	end

	context 'Hiding the Options Tab' do
		it 'should still be hidden with an invalid form' do
			hide_options_tab = @page.hide_tab_4

			# Confirm the icon is for hiding
			hide_options_tab[:class].should eq 'tab-on'

			hide_options_tab.trigger('click')

			# Confirm the icon is for showing
			hide_options_tab[:class].should eq 'tab-off'

			@page.submit_button.click
			no_php_js_errors

			@page.should have_alert

			hide_options_tab = @page.hide_tab_4
			hide_options_tab[:class].should eq 'tab-off'
		end

		it 'should be hidden when saved' do
			hide_options_tab = @page.hide_tab_4

			# Confirm the icon is for hiding
			hide_options_tab[:class].should eq 'tab-on'

			hide_options_tab.trigger('click')

			# Confirm the icon is for showing
			hide_options_tab[:class].should eq 'tab-off'

			@page.layout_name.set 'Default'
			@page.submit_button.click
			no_php_js_errors

			@page.edit(1)
			hide_options_tab = @page.hide_tab_4
			hide_options_tab[:class].should eq 'tab-off'
		end

		# Bug #21191
		context 'Channel has no Categories' do
			before(:each) do
				visit '/system/index.php?/cp/channels/edit/1'
				channel = ChannelCreate.new
				channel.cat_group.each {|cat| cat.set false}
				channel.submit
				@page.load
			end

			it 'should still be hidden with an invalid form' do
				hide_options_tab = @page.hide_tab_3

				# Confirm the icon is for hiding
				hide_options_tab[:class].should eq 'tab-on'

				hide_options_tab.trigger('click')

				# Confirm the icon is for showing
				hide_options_tab[:class].should eq 'tab-off'

				@page.submit_button.click
				no_php_js_errors

				@page.should have_alert

				hide_options_tab = @page.hide_tab_3
				hide_options_tab[:class].should eq 'tab-off'
			end

			it 'should be hidden when saved' do
				hide_options_tab = @page.hide_tab_3

				# Confirm the icon is for hiding
				hide_options_tab[:class].should eq 'tab-on'

				hide_options_tab.trigger('click')

				# Confirm the icon is for showing
				hide_options_tab[:class].should eq 'tab-off'

				@page.layout_name.set 'Default'
				@page.submit_button.click
				no_php_js_errors

				@page.edit(1)
				hide_options_tab = @page.hide_tab_3
				hide_options_tab[:class].should eq 'tab-off'
			end
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
