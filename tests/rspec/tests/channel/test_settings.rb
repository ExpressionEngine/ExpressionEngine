require './bootstrap.rb'

feature 'Channel Settings' do

  before(:each) do
    cp_session
    @page = ChannelCreate.new
  end

  it 'shows the Channel Settings page' do
    @page.load_edit_for_channel(1)
    no_php_js_errors

    @page.all_there?.should == true
  end

  it 'should validate the form and reject XSS' do
    @page.load_edit_for_channel(2)
    no_php_js_errors

    @page.settings_tab.click
    @page.channel_description.set $xss_vector
    @page.channel_description.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_form_errors(@page)
    should_have_error_text(@page.channel_description, $xss_error)

    @page.channel_url.set $xss_vector
    @page.channel_url.trigger 'blur'
    @page.wait_for_error_message_count(2)
    should_have_form_errors(@page)
    should_have_error_text(@page.channel_url, $xss_error)

    @page.comment_url.set $xss_vector
    @page.comment_url.trigger 'blur'
    @page.wait_for_error_message_count(3)
    should_have_form_errors(@page)
    should_have_error_text(@page.comment_url, $xss_error)

    @page.search_results_url.set $xss_vector
    @page.search_results_url.trigger 'blur'
    @page.wait_for_error_message_count(4)
    should_have_form_errors(@page)
    should_have_error_text(@page.search_results_url, $xss_error)

    @page.rss_url.set $xss_vector
    @page.rss_url.trigger 'blur'
    @page.wait_for_error_message_count(5)
    should_have_form_errors(@page)
    should_have_error_text(@page.rss_url, $xss_error)

    @page.default_entry_title.set $xss_vector
    @page.default_entry_title.trigger 'blur'
    @page.wait_for_error_message_count(6)
    should_have_form_errors(@page)
    should_have_error_text(@page.default_entry_title, $xss_error)

    @page.url_title_prefix.set $xss_vector
    @page.url_title_prefix.trigger 'blur'
    @page.wait_for_error_message_count(8)
    should_have_form_errors(@page)
    should_have_error_text(@page.url_title_prefix, $xss_error)

    @page.url_title_prefix.set 'test'
    @page.url_title_prefix.trigger 'blur'
    @page.wait_for_error_message_count(6)
    should_have_form_errors(@page)
    should_have_no_error_text(@page.url_title_prefix)

    @page.url_title_prefix.set 'test test'
    @page.url_title_prefix.trigger 'blur'
    @page.wait_for_error_message_count(7)
    should_have_form_errors(@page)
    should_have_error_text(@page.url_title_prefix, 'This field may only contain alpha-numeric characters, underscores, and dashes.')

    @page.max_revisions.set 'test'
    @page.max_revisions.trigger 'blur'
    @page.wait_for_error_message_count(8)
    should_have_form_errors(@page)
    # Commented out for now, checking for error text is a little
    # more tricky since the field is inside a special note div
    # should_have_error_text(@page.max_revisions, $integer_error)

    valid_emails = 'This field must contain a valid email address.'

    @page.channel_notify_emails.set 'test'
    @page.channel_notify_emails.trigger 'blur'
    @page.wait_for_error_message_count(9)
    should_have_form_errors(@page)
    should_have_error_text(@page.channel_notify_emails, valid_emails)

    @page.comment_notify_emails.set 'test'
    @page.comment_notify_emails.trigger 'blur'
    @page.wait_for_error_message_count(10)
    should_have_form_errors(@page)
    should_have_error_text(@page.comment_notify_emails, valid_emails)

    valid_number = 'This field must contain only positive numbers.'

    @page.comment_max_chars.set 'test'
    @page.comment_max_chars.trigger 'blur'
    @page.wait_for_error_message_count(11)
    should_have_form_errors(@page)
    should_have_error_text(@page.comment_max_chars, valid_number)

    @page.comment_timelock.set 'test'
    @page.comment_timelock.trigger 'blur'
    @page.wait_for_error_message_count(12)
    should_have_form_errors(@page)
    should_have_error_text(@page.comment_timelock, valid_number)

    @page.comment_expiration.set 'test'
    @page.comment_expiration.trigger 'blur'
    @page.wait_for_error_message_count(13)
    should_have_form_errors(@page)
    # Commented out for now, checking for error text is a little
    # more tricky since the field is inside a special note div
    # should_have_error_text(@page.comment_expiration, $integer_error)

    # Fix everything

    @page.channel_description.set 'test'
    @page.channel_description.trigger 'blur'
    @page.wait_for_error_message_count(12)
    should_have_form_errors(@page)
    should_have_no_error_text(@page.channel_description)

    @page.channel_url.set 'test'
    @page.channel_url.trigger 'blur'
    @page.wait_for_error_message_count(11)
    should_have_form_errors(@page)
    should_have_no_error_text(@page.channel_url)

    @page.comment_url.set 'test'
    @page.comment_url.trigger 'blur'
    @page.wait_for_error_message_count(10)
    should_have_form_errors(@page)
    should_have_no_error_text(@page.comment_url)

    @page.search_results_url.set 'test'
    @page.search_results_url.trigger 'blur'
    @page.wait_for_error_message_count(9)
    should_have_form_errors(@page)
    should_have_no_error_text(@page.search_results_url)

    @page.rss_url.set 'test'
    @page.rss_url.trigger 'blur'
    @page.wait_for_error_message_count(8)
    should_have_form_errors(@page)
    should_have_no_error_text(@page.rss_url)

    @page.default_entry_title.set 'test'
    @page.default_entry_title.trigger 'blur'
    @page.wait_for_error_message_count(7)
    should_have_form_errors(@page)
    should_have_no_error_text(@page.default_entry_title)

    @page.url_title_prefix.set 'test'
    @page.url_title_prefix.trigger 'blur'
    @page.wait_for_error_message_count(6)
    should_have_form_errors(@page)
    should_have_no_error_text(@page.url_title_prefix)

    @page.max_revisions.set '0'
    @page.max_revisions.trigger 'blur'
    @page.wait_for_error_message_count(5)
    should_have_form_errors(@page)
    should_have_no_error_text(@page.max_revisions)

    @page.channel_notify_emails.set 'test@fake.com,test2@fake.com'
    @page.channel_notify_emails.trigger 'blur'
    @page.wait_for_error_message_count(4)
    should_have_form_errors(@page)
    should_have_no_error_text(@page.channel_notify_emails)

    @page.comment_notify_emails.set 'test@fake.com'
    @page.comment_notify_emails.trigger 'blur'
    @page.wait_for_error_message_count(3)
    should_have_form_errors(@page)
    should_have_no_error_text(@page.comment_notify_emails)

    @page.comment_max_chars.set '0'
    @page.comment_max_chars.trigger 'blur'
    @page.wait_for_error_message_count(2)
    should_have_form_errors(@page)
    should_have_no_error_text(@page.comment_max_chars)

    @page.comment_timelock.set '0'
    @page.comment_timelock.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_form_errors(@page)
    should_have_no_error_text(@page.comment_timelock)

    @page.comment_expiration.set '0'
    @page.comment_expiration.trigger 'blur'
    @page.wait_for_error_message_count(0)
    should_have_no_form_errors(@page)
    should_have_no_error_text(@page.comment_expiration)

    no_php_js_errors

    @page.submit
    @page.should have_text 'Channel Updated'
  end

  it 'should save and load the settings' do
    @page.load_edit_for_channel(2)
    no_php_js_errors

    @page.settings_tab.click
    @page.channel_description.set 'Some description'
    @page.channel_lang.choose_radio_option 'english'
    @page.channel_url.set 'http://someurl/channel'
    @page.comment_url.set 'http://someurl/channel/comment'
    @page.search_results_url.set 'http://someurl/channel/search/results'
    @page.rss_url.set 'http://someurl/channel/rss'
    @page.live_look_template.choose_radio_option '16'

    @page.default_entry_title.set 'Default title'
    @page.url_title_prefix.set 'default-title'

    @page.deft_status.choose_radio_option 'closed'
    @page.deft_category.choose_radio_option '1'
    @page.search_excerpt.choose_radio_option '1'

    @page.channel_html_formatting.choose_radio_option 'none'
    @page.channel_allow_img_urls.click
    # @page.channel_auto_link_urls.click

    @page.default_status.choose_radio_option 'closed'
    @page.allow_guest_posts.click

    @page.enable_versioning.click
    @page.max_revisions.set '20'
    @page.clear_versioning_data.click

    @page.comment_notify_authors.click
    @page.channel_notify.click
    @page.channel_notify_emails.set 'trey@treyanastasio.com,mike@mikegordon.com'
    @page.comment_notify.click
    @page.comment_notify_emails.set 'page@pagemcconnell.com,jon@jonfishman.com'

    @page.comment_system_enabled.click
    @page.deft_comments.click
    @page.comment_require_membership.click
    @page.comment_require_email.click
    @page.comment_moderate.click
    @page.comment_max_chars.set '40'
    @page.comment_timelock.set '50'
    @page.comment_expiration.set '60'
    @page.apply_expiration_to_existing.click
    @page.comment_text_formatting.choose_radio_option 'none'
    @page.comment_html_formatting.choose_radio_option 'all'
    @page.comment_allow_img_urls.click
    @page.comment_auto_link_urls.click

    @page.save_button.click

    no_php_js_errors
    @page.should have_text 'Channel Updated'

    @page.find('.tabs li', {text: 'Settings'}).click

    @page.channel_description.value.should == 'Some description'
    @page.channel_lang.has_checked_radio('english').should == true

    @page.channel_url.value.should == 'http://someurl/channel'
    @page.comment_url.value.should == 'http://someurl/channel/comment'
    @page.search_results_url.value.should == 'http://someurl/channel/search/results'
    @page.rss_url.value.should == 'http://someurl/channel/rss'
    @page.live_look_template.has_checked_radio('16').should == true

    @page.default_entry_title.value.should == 'Default title'
    @page.url_title_prefix.value.should == 'default-title'
    @page.deft_status.has_checked_radio('closed').should == true
    @page.deft_category.has_checked_radio('1').should == true
    @page.search_excerpt.has_checked_radio('1').should == true

    @page.channel_html_formatting.has_checked_radio('none').should == true
    @page.channel_allow_img_urls[:class].should include "off"
    @page.channel_auto_link_urls[:class].should include "on"

    @page.default_status.has_checked_radio('closed').should == true
    @page.default_author.has_checked_radio('1').should == true
    @page.allow_guest_posts[:class].should include "on"

    @page.enable_versioning[:class].should include "on"
    @page.max_revisions.value.should == '20'
    @page.clear_versioning_data.checked?.should == false

    @page.comment_notify_authors[:class].should include "on"
    @page.channel_notify[:class].should include "on"
    @page.channel_notify_emails.value.should == 'trey@treyanastasio.com,mike@mikegordon.com'
    @page.comment_notify[:class].should include "on"
    @page.comment_notify_emails.value.should == 'page@pagemcconnell.com,jon@jonfishman.com'

    @page.comment_system_enabled[:class].should include "off"
    @page.deft_comments[:class].should include "off"
    @page.comment_require_membership[:class].should include "on"
    @page.comment_require_email[:class].should include "off"
    @page.comment_moderate[:class].should include "on"
    @page.comment_max_chars.value.should ==  '40'
    @page.comment_timelock.value.should ==  '50'
    @page.comment_expiration.value.should ==  '60'
    @page.apply_expiration_to_existing.checked?.should == false
    @page.comment_text_formatting.has_checked_radio('none').should == true
    @page.comment_html_formatting.has_checked_radio('all').should == true
    @page.comment_allow_img_urls[:class].should include "on"
    @page.comment_auto_link_urls[:class].should include "off"
  end

  # TODO: Test to make sure checkboxes that apply settings to all
  # comments/entries actually do so

end
