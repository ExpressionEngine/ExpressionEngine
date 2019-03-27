class ControlPanelPage < SitePrism::Page

  section :main_menu, MenuSection, 'div.nav-main-wrap'
  elements :submit_buttons, '.form-btns .btn'
  element :fieldset_errors, '.fieldset-invalid'
  element :settings_btn, '.nav-main-develop a.nav-settings'
  elements :error_messages, 'em.ee-form-error-message'

  # Main Section
  element :page_title, '.wrap .box h1'

  # Tables
  element :select_all, 'th.text--center input'
  element :sort_col, 'table th.column-sort---active'
  elements :sort_links, 'table a.column-sort'
  element :bulk_action, 'form fieldset.tbl-bulk-act select[name="bulk_action"]'
  element :action_submit_button, 'form fieldset.tbl-bulk-act .submit'

  # Pagination
  element :pagination, 'div.paginate'
  elements :pages, 'div.paginate ul li a'

  # Alert
  element :alert, 'div.app-notice'
  element :alert_success, 'div.app-notice---success'
  element :alert_important, 'div.app-notice---important'
  element :alert_error, 'div.app-notice---error'

  # Modals
  element :modal, 'div.modal'
  element :modal_title, 'div.modal h1'
  element :modal_submit_button, 'div.modal input.btn'

  # Grid
  element :grid_add_no_results, 'tr.no-results a[rel="add_row"]'
  element :grid_add, 'ul.toolbar li.add a'

  # Breadcrumb
  element :breadcrumb, 'ul.breadcrumb'

  # Sidebar
  element :sidebar, 'div.sidebar'

  # Tabs
  element :tab_bar, 'div.tab-wrap'
  elements :tabs, 'div.tab-wrap ul.tabs li'

  def is_404?
    page_title.text.start_with? '404'
  end

  def open_dev_menu
    main_menu.dev_menu.click
  end

  def submit
    submit_buttons[0].click
  end

  def submit_enabled?
    button_value = submit_buttons[0].value
    if submit_buttons[0].tag_name == 'button' then
      button_value = submit_buttons[0].text
    end

    return button_value.downcase != 'errors found' && submit_buttons[0][:disabled] != true
  end

  # Waits until the error message is gone before proceeding;
  # if we just check for invisible but it's already gone,
  # Capybara will complain, so we must do this
  def wait_for_error_message_count(count, seconds = 5)

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
    while element_count != count && i < (seconds * 100)
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

    # Element is still there after our timeout? No good.
    if element_count != count && i == (seconds * 100)
      raise StandardError, "Wrong number of validation errors. Got #{element_count}, expected #{count}."
    end
  end
end
