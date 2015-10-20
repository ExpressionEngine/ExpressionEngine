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
			no_php_js_errors
			hide_options_tab = @page.hide_tab_4
			hide_options_tab[:class].should eq 'tab-off'
		end
	end

	context 'Hiding fields in the Options Tab' do
		before(:each) do
			@page.tab_4.click
		end

		it 'should still be hidden with an invalid form' do
			field = @page.fields[0]
			hide_tool = @page.visibiltiy_tool(field)

			# Confirm the tool is for hiding
			hide_tool[:class].should eq 'hide'
			hide_tool.find('a').click

			# Confirm the tool is for unhiding
			hide_tool[:class].should eq 'unhide'

			@page.submit_button.click
			no_php_js_errors

			@page.should have_alert
			@page.tab_4.click

			field = @page.fields[0]
			hide_tool = @page.visibiltiy_tool(field)
			hide_tool[:class].should eq 'unhide'
		end

		it 'should be hidden when saved' do
			field = @page.fields[0]
			hide_tool = @page.visibiltiy_tool(field)

			# Confirm the tool is for hiding
			hide_tool[:class].should eq 'hide'
			hide_tool.find('a').click

			# Confirm the tool is for unhiding
			hide_tool[:class].should eq 'unhide'

			@page.layout_name.set 'Default'
			@page.submit_button.click
			no_php_js_errors

			@page.edit(1)
			no_php_js_errors
			@page.tab_4.click

			field = @page.fields[0]
			hide_tool = @page.visibiltiy_tool(field)
			hide_tool[:class].should eq 'unhide'
		end
	end

	it 'can move a field out the Options tab' do
		@page.tab_4.click
		field = @page.fields[0]
		field_text = field.text
		move_tool = @page.move_tool(field)

		move_tool.drag_to(@page.publish_tab)

		@page.publish_tab[:class].should include 'act'
		@page.tab_4[:class].should_not include 'act'
		@page.fields[0].text.should eq field_text
	end

	it 'can add a new tab' do
		new_tab_name = "New Tab"

		tabs = @page.tabs.length
		@page.add_tab_button.click
		@page.wait_until_add_tab_modal_visible
		@page.add_tab_modal_tab_name.set new_tab_name
		@page.add_tab_modal_submit_button.click

		@page.tabs.length.should eq tabs + 1
		@page.tabs[-1].text.should include new_tab_name

		@page.layout_name.set 'Default'
		@page.submit_button.click
		no_php_js_errors

		@page.edit(1)
		no_php_js_errors

		@page.tab_bar.text.should include new_tab_name
	end

	it 'can move a field to a new tab' do
		new_tab_name = "New Tab"

		tabs = @page.tabs.length
		@page.add_tab_button.click
		@page.wait_until_add_tab_modal_visible
		@page.add_tab_modal_tab_name.set new_tab_name
		@page.add_tab_modal_submit_button.click

		new_tab = @page.tabs[-1]
		@page.tabs.length.should eq tabs + 1
		new_tab.text.should include new_tab_name

		field = @page.fields[0]
		field_text = field.text
		move_tool = @page.move_tool(field)

		move_tool.drag_to(new_tab)
		new_tab[:class].should include 'act'
		@page.fields[0].text.should eq field_text
	end

	it 'cannot hide a tab with a required field' do
	end

	it 'makes a hidden tab visible when a required field is moved into it' do
	end

	# This was a bug in 3.0
	it 'can create two layouts for the same channel' do
		@page.layout_name.set 'Default'
		@page.submit_button.click
		no_php_js_errors

		@page.load
		no_php_js_errors
		@page.displayed?
	end

	# Bug #21191
	context '(Bug #21191) Channel has no Categories' do
		before(:each) do
			visit '/system/index.php?/cp/channels/edit/1'
			channel = ChannelCreate.new
			channel.cat_group.each {|cat| cat.set false}
			channel.submit
			@page.load
		end

		context 'Hiding the Options Tab' do
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
				no_php_js_errors
				hide_options_tab = @page.hide_tab_3
				hide_options_tab[:class].should eq 'tab-off'
			end
		end

		context 'Hiding fields in the Options Tab' do
			before(:each) do
				@page.tab_3.click
			end

			it 'should still be hidden with an invalid form' do
				field = @page.fields[0]
				hide_tool = @page.visibiltiy_tool(field)

				# Confirm the tool is for hiding
				hide_tool[:class].should eq 'hide'
				hide_tool.find('a').click

				# Confirm the tool is for unhiding
				hide_tool[:class].should eq 'unhide'

				@page.submit_button.click
				no_php_js_errors

				@page.should have_alert
				@page.tab_3.click

				field = @page.fields[0]
				hide_tool = @page.visibiltiy_tool(field)
				hide_tool[:class].should eq 'unhide'
			end

			it 'should be hidden when saved' do
				field = @page.fields[0]
				hide_tool = @page.visibiltiy_tool(field)

				# Confirm the tool is for hiding
				hide_tool[:class].should eq 'hide'
				hide_tool.find('a').click

				# Confirm the tool is for unhiding
				hide_tool[:class].should eq 'unhide'

				@page.layout_name.set 'Default'
				@page.submit_button.click
				no_php_js_errors

				@page.edit(1)
				no_php_js_errors
				@page.tab_3.click

				field = @page.fields[0]
				hide_tool = @page.visibiltiy_tool(field)
				hide_tool[:class].should eq 'unhide'
			end
		end

		it 'can move a field out the Options tab' do
		end
	end

	# Bug #21220
	it 'can move Entry Date to a new tab and retain the "required" class' do
		@page.date_tab.click

		field = @page.fields[0]
		# Confirm we have the right field
		field.text.should include 'Entry date'
		field[:class].should include 'required'
		move_tool = @page.move_tool(field)

		move_tool.drag_to(@page.publish_tab)
		@page.fields[0].text.should include 'Entry date'
		@page.fields[0][:class].should include 'required'
	end

end
