class ControlPanelPage < SitePrism::Page

	section :main_menu, MenuSection, 'section.menu-wrap'
	elements :submit_buttons, '.form-ctrls input.btn'
	element :fieldset_errors, '.invalid'
	element :settings_btn, 'b.ico.settings'
	elements :error_messages, 'em.ee-form-error-message'

	# Tables
	element :select_all, 'th.check-ctrl input'
	element :sort_col, 'table th.highlight'
	elements :sort_links, 'table a.sort'
	element :bulk_action, 'form fieldset.tbl-bulk-act select[name="bulk_action"]'
	element :action_submit_button, 'form fieldset.tbl-bulk-act input.submit'

	# Pagination
	element :pagination, 'div.paginate'
	elements :pages, 'div.paginate ul li a'

	# Alert
	element :alert, 'div.alert'

	# Modals
	element :modal, 'div.modal'
	element :modal_title, 'div.modal div.box h1'
	element :modal_submit_button, 'div.modal .form-ctrls input.btn'

	# Grid
	element :grid_add_no_results, 'tr.no-results a.btn'
	element :grid_add, 'ul.toolbar li.add a'

	# Breadcrumb
	element :breadcrumb, 'ul.breadcrumb'

	# Sidebar
	element :sidebar, 'div.sidebar'

	# Tabs
	element :tab_bar, 'div.tab-wrap'
	elements :tabs, 'div.tab-wrap ul.tabs li'

	def open_dev_menu
		main_menu.dev_menu.click
	end

	def submit
		submit_buttons[0].click
	end

	def submit_enabled?
		submit_buttons[0].value != 'Errors Found' &&
		submit_buttons[0][:disabled] != true
	end

	# Waits until the error message is gone before proceeding;
	# if we just check for invisible but it's already gone,
	# Capybara will complain, so we must do this
	def wait_for_error_message_count(count)

		# Wait for any AJAX requests or other scripts that have backed up
		ajax = false
		while ajax == false do
			ajax = (self.evaluate_script('$.active') == 0)
		end

		i = 0;
		element_count = nil;
		# This is essentially our own version of wait_until_x_invisible/visible,
		# except we're not going to throw an exception if the element
		# is already gone thus breaking our test; if the element is already
		# gone, AJAX and the DOM have already done their job
		while element_count != count && i < 500
			begin
				element_count = self.error_messages.size
			rescue
				# If we're here and we're waiting for 0 errors,
				# an exception was likely thrown because there are
				# no errors, so bail out of loop
				if count == 0
					element_count = 0
				end
			end
			sleep 0.01
			i += 1 # Prevent infinite loop
		end
	end
end
