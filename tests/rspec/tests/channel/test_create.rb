require './bootstrap.rb'

feature 'Channel Create/Edit' do

  before(:each) do
    cp_session
    @page = Channel.new
    @page.load
    no_php_js_errors

    @channel_name_error = 'This field may only contain alpha-numeric characters, underscores, and dashes.'
  end

  it 'shows the Channel Create/Edit page' do
    @page.all_there?.should == true
    @page.should have_text 'New Channel'
  end

  it 'should validate regular fields' do
    @page.submit

    no_php_js_errors
    should_have_form_errors(@page)
    @page.should have_text 'Cannot Create Channel'
    should_have_error_text(@page.channel_title, $required_error)
    should_have_error_text(@page.channel_name, $required_error)

    # AJAX validation
    # Required name
    @page.load
    @page.channel_title.set ''
    @page.channel_title.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_error_text(@page.channel_title, $required_error)
    should_have_form_errors(@page)

    @page.channel_title.set 'Test'
    @page.channel_title.trigger 'blur'
    @page.wait_for_error_message_count(0)
    should_have_no_error_text(@page.channel_title)
    should_have_no_form_errors(@page)

    # Invalid channel short name
    @page.channel_name.set 'test test'
    @page.channel_name.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_error_text(@page.channel_name, @channel_name_error)
    should_have_form_errors(@page)

    @page.channel_name.set 'test'
    @page.channel_name.trigger 'blur'
    @page.wait_for_error_message_count(0)
    should_have_no_error_text(@page.channel_title)
    should_have_no_form_errors(@page)

    # Duplicate channel short name
    @page.channel_name.set 'news'
    @page.channel_name.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_error_text(@page.channel_name, @unique)
    should_have_form_errors(@page)

    # Duplicate channel title
    @page.channel_title.set 'News'
    @page.channel_title.trigger 'blur'
    @page.wait_for_error_message_count(2)
    should_have_error_text(@page.channel_title, @unique)
    should_have_form_errors(@page)
  end

  it 'should reject XSS' do
    @page.channel_title.set $xss_vector
    @page.channel_title.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_error_text(@page.channel_title, $xss_error)
    should_have_form_errors(@page)
  end

  it 'should repopulate the form on validation error' do
    @page.channel_title.set 'Test'

    # Channel name should autopopulate
    @page.channel_name.value.should == 'test'

    @page.duplicate_channel_prefs.choose_radio_option '1'

    @page.fields_tab.click
    @page.field_groups[0].click

    # Check both category groups
    @page.categories_tab.click
    @page.cat_group[0].click
    @page.cat_group[1].click

    # Sabbotage the channel name and submit
    @page.channel_tab.click
    @page.channel_name.set 'test test'
    @page.submit

    @page.should have_text 'Cannot Create Channel'
    should_have_error_text(@page.channel_name, @channel_name_error)
    should_have_form_errors(@page)

    @page.channel_title.value.should == 'Test'
    @page.channel_name.value.should == 'test test'
    @page.duplicate_channel_prefs.has_checked_radio('1').should == true

    @page.fields_tab.click
    @page.field_groups[0].checked?.should == true

    @page.categories_tab.click
    @page.cat_group[0].checked?.should == true
    @page.cat_group[1].checked?.should == true
  end

  it 'should save a new channel and load edit form' do
    @page.channel_title.set 'Test'

    # Channel name should autopopulate
    @page.channel_name.value.should == 'test'

    @page.fields_tab.click
    @page.field_groups[0].click

    # Check both category groups
    @page.categories_tab.click
    @page.cat_group[0].click
    @page.cat_group[1].click

    @page.submit
    no_php_js_errors

    @page.should have_text 'Channel Created'

    @page.should have_text 'Edit Channel'

    # These should be gone on edit
    @page.should have_no_duplicate_channel_prefs
    @page.should have_no_text 'Warning: Channels require'

    should_have_no_form_errors(@page)

    @page.channel_title.value.should == 'Test'
    @page.channel_name.value.should == 'test'

    @page.fields_tab.click
    @page.field_groups[0].checked?.should == true

    @page.categories_tab.click
    @page.cat_group[0].checked?.should == true
    @page.cat_group[1].checked?.should == true
  end

  it 'should edit an existing channel' do
    @page.load_edit_for_channel(1)
    no_php_js_errors

    # These should be gone on edit
    @page.should have_no_duplicate_channel_prefs
    @page.should have_no_text 'Warning: Channels require'

    old_channel_name = @page.channel_name.value

    @page.channel_title.set 'New channel'

    # Channel short name should not change when title is edited
    @page.channel_name.value.should == old_channel_name

    @page.submit
    no_php_js_errors

    @page.should have_text 'Channel Updated'
    @page.channel_title.value.should == 'New channel'
  end

  # Issue #1010
	it 'should allow setting field to None' do
    @page.load_edit_for_channel(1)
    no_php_js_errors

    @page.submit
  end

  it 'should duplicate an existing channel' do
    # Set some arbitrary settings on the News channel
    channel_settings = Channel.new
    channel_settings.load_edit_for_channel(2) # 2nd row, not channel id 2
    channel_settings.settings_tab.click
    channel_settings.channel_description.set 'Some description'
    channel_settings.channel_lang.choose_radio_option 'english'
    channel_settings.channel_url.set 'http://someurl/channel'
    channel_settings.comment_url.set 'http://someurl/channel/comment'
    channel_settings.search_results_url.set 'http://someurl/channel/search/results'
    channel_settings.rss_url.set 'http://someurl/channel/rss'
    channel_settings.preview_url.set 'someurl/channel/{entry_id}'

    channel_settings.default_entry_title.set 'Default title'
    channel_settings.url_title_prefix.set 'default-title'

    channel_settings.deft_status.choose_radio_option 'closed'
    channel_settings.deft_category.choose_radio_option '1'
    channel_settings.search_excerpt.choose_radio_option '1'

    channel_settings.channel_html_formatting.choose_radio_option 'none'
    channel_settings.channel_allow_img_urls.click
    # channel_settings.channel_auto_link_urls.click

    channel_settings.default_status.choose_radio_option 'closed'
    channel_settings.allow_guest_posts.click

    channel_settings.enable_versioning.click
    channel_settings.max_revisions.set '20'
    channel_settings.clear_versioning_data.click

    channel_settings.comment_notify_authors.click
    channel_settings.channel_notify.click
    channel_settings.channel_notify_emails.set 'trey@treyanastasio.com,mike@mikegordon.com'
    channel_settings.comment_notify.click
    channel_settings.comment_notify_emails.set 'page@pagemcconnell.com,jon@jonfishman.com'

    channel_settings.comment_system_enabled.click
    channel_settings.deft_comments.click
    channel_settings.comment_require_membership.click
    channel_settings.comment_require_email.click
    channel_settings.comment_moderate.click
    channel_settings.comment_max_chars.set '40'
    channel_settings.comment_timelock.set '50'
    channel_settings.comment_expiration.set '60'
    channel_settings.apply_expiration_to_existing.click
    channel_settings.comment_text_formatting.choose_radio_option 'none'
    channel_settings.comment_html_formatting.choose_radio_option 'all'
    channel_settings.comment_allow_img_urls.click
    channel_settings.comment_auto_link_urls.click

    channel_settings.save_and_new_button.click
    channel_settings.should have_text 'Channel Updated'

    # Create new channel, ensure field groups and things were duplicated
    @page.channel_title.set 'Test'
    @page.duplicate_channel_prefs.choose_radio_option '1'

    @page.save_button.click
    no_php_js_errors

    @page.should have_text 'Channel Created'

    @page.channel_title.value.should == 'Test'
    @page.channel_name.value.should == 'test'

    @page.fields_tab.click
    @page.field_groups[0].checked?.should == false
    @page.field_groups[1].checked?.should == true

    @page.categories_tab.click
    @page.cat_group[0].checked?.should == false
    @page.cat_group[1].checked?.should == true

    # Now make sure settings were duplicated
    @page.settings_tab.click
    @page.channel_description.value.should == 'Some description'
    @page.channel_lang.has_checked_radio('english').should == true

    @page.channel_url.value.should == 'http://someurl/channel'
    @page.comment_url.value.should == 'http://someurl/channel/comment'
    @page.search_results_url.value.should == 'http://someurl/channel/search/results'
    @page.rss_url.value.should == 'http://someurl/channel/rss'
    @page.preview_url.value.should == 'someurl/channel/{entry_id}'

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
end
