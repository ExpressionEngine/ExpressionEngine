require './bootstrap.rb'

feature 'Ban Settings', () => {
  beforeEach(function() {
    cy.auth();
    page = BansMembers.new
    page.load()
    cy.hasNoErrors()
  }

  it('shows the Ban Settings page', () => {
    page.all_there?.should == true
  }

  it('should load current settings into form fields', () => {
    page.banned_ips.value.should == ee_config(item: 'banned_ips')
    page.banned_emails.value.should == ee_config(item: 'banned_emails')
    page.banned_usernames.value.should == ee_config(item: 'banned_usernames')
    page.banned_screen_names.value.should == ee_config(item: 'banned_screen_names')
    page.ban_action_options.has_checked_radio(ee_config(item: 'ban_action')).should == true
    page.ban_message.value.should == ee_config(item: 'ban_message')
    page.ban_destination.value.should == ee_config(item: 'ban_destination')
  }

  it('should reject XSS', () => {
    page.banned_ips.set $xss_vector
    page.banned_ips.trigger 'blur'
    page.wait_for_error_message_count(1)
    should_have_error_text(page.banned_ips, $xss_error)
    should_have_form_errors(page)

    page.banned_emails.set $xss_vector
    page.banned_emails.trigger 'blur'
    page.wait_for_error_message_count(2)
    should_have_error_text(page.banned_emails, $xss_error)
    should_have_error_text(page.banned_ips, $xss_error)
    should_have_form_errors(page)

    page.banned_usernames.set $xss_vector
    page.banned_usernames.trigger 'blur'
    page.wait_for_error_message_count(3)
    should_have_error_text(page.banned_usernames, $xss_error)
    should_have_error_text(page.banned_emails, $xss_error)
    should_have_error_text(page.banned_ips, $xss_error)
    should_have_form_errors(page)

    page.banned_screen_names.set $xss_vector
    page.banned_screen_names.trigger 'blur'
    page.wait_for_error_message_count(4)
    should_have_error_text(page.banned_screen_names, $xss_error)
    should_have_error_text(page.banned_usernames, $xss_error)
    should_have_error_text(page.banned_emails, $xss_error)
    should_have_error_text(page.banned_ips, $xss_error)
    should_have_form_errors(page)
  }

  it('should save and load the settings', () => {
    page.banned_ips.set 'Dummy IPs'
    page.banned_emails.set 'Dummy Emails'
    page.banned_usernames.set 'Dummy Usernames'
    page.banned_screen_names.set 'Dummy Screen Names'
    page.ban_action_options.choose_radio_option('message')
    page.ban_message.set 'Dummy Message'
    page.ban_destination.set 'Dummy Destination'
    page.submit

    page.should have_text 'Ban Settings updated'
    // Ban settings adds a newline to queue admins for correct legible input
    page.banned_ips.value.should == "Dummy IPs\n"
    page.banned_emails.value.should == "Dummy Emails\n"
    page.banned_usernames.value.should == "Dummy Usernames\n"
    page.banned_screen_names.value.should == "Dummy Screen Names\n"
    page.ban_action_options.has_checked_radio('message').should == true
    page.ban_message.value.should == "Dummy Message"
    page.ban_destination.value.should == "Dummy Destination"
  }
}
