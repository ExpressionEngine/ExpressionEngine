require './bootstrap.rb'

feature 'Channel Create/Edit' do

  before(:each) do
    cp_session
    @page = ChannelCreate.new
    @page.load
    no_php_js_errors

    @channel_name_error = 'Your channel name must contain only alpha-numeric characters and no spaces.'
    @dupe_channel_name = 'This channel name is already taken.'
  end

  it 'shows the Channel Create/Edit page' do
    @page.all_there?.should == true
    @page.should have_text 'Create Channel'

    # Warning should show only on create
    @page.should have_text 'Warning: Channels require'
  end

  it 'should validate regular fields' do    
    @page.submit

    no_php_js_errors
    should_have_form_errors(@page)
    @page.should have_text 'Attention: Channel not saved'
    should_have_error_text(@page.channel_title, $required_error)
    should_have_error_text(@page.channel_name, $required_error)

    # AJAX validation
    # Required name
    @page.load
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
    should_have_error_text(@page.channel_name, @dupe_channel_name)
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

    @page.duplicate_channel_prefs.select 'News'
    @page.status_group.select 'Statuses'
    @page.field_group.select 'About'

    # Check both category groups
    @page.cat_group[0].click
    @page.cat_group[1].click

    # Sabbotage the channel name and submit
    @page.channel_name.set 'test test'
    @page.submit

    @page.should have_text 'Attention: Channel not saved'
    should_have_error_text(@page.channel_name, @channel_name_error)
    should_have_form_errors(@page)

    @page.channel_title.value.should == 'Test'
    @page.channel_name.value.should == 'test test'
    @page.duplicate_channel_prefs.value.should == '1'
    @page.status_group.value.should == '1'
    @page.field_group.value.should == '2'

    @page.cat_group[0].checked?.should == true
    @page.cat_group[1].checked?.should == true
  end

  it 'should save a new channel and load edit form' do
    @page.channel_title.set 'Test'

    # Channel name should autopopulate
    @page.channel_name.value.should == 'test'

    @page.status_group.select 'Statuses'
    @page.field_group.select 'About'

    # Check both category groups
    @page.cat_group[0].click
    @page.cat_group[1].click

    @page.submit
    no_php_js_errors

    @page.should have_text 'Channel saved'
    @page.should have_text 'Edit Channel'

    # These should be gone on edit
    @page.should have_no_duplicate_channel_prefs
    @page.should have_no_text 'Warning: Channels require'

    should_have_no_form_errors(@page)

    @page.channel_title.value.should == 'Test'
    @page.channel_name.value.should == 'test'
    @page.status_group.value.should == '1'
    @page.field_group.value.should == '2'

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

    @page.should have_text 'Channel saved'
    @page.channel_title.value.should == 'New channel'
  end

  it 'should duplicate an existing channel' do
    # Set some arbitrary settings on the News channel
    channel_settings = ChannelSettings.new
    channel_settings.load_settings_for_channel(2)
    channel_settings.channel_description.set 'Some description'
    channel_settings.channel_url.set 'http://someurl/channel'
    channel_settings.comment_url.set 'http://someurl/channel/comment'
    channel_settings.search_results_url.set 'http://someurl/channel/search/results'
    channel_settings.rss_url.set 'http://someurl/channel/rss'
    channel_settings.live_look_template.select 'search/index'

    channel_settings.default_entry_title.set 'Default title'
    channel_settings.url_title_prefix.set 'default-title'
    channel_settings.deft_status.select 'Closed'
    channel_settings.deft_category.select 'News Categories: News'
    channel_settings.search_excerpt.select 'Body'

    channel_settings.channel_html_formatting.select 'Convert to HTML entities'
    channel_settings.channel_allow_img_urls[1].click
    channel_settings.channel_auto_link_urls[0].click
    channel_settings.show_button_cluster[1].click

    channel_settings.default_status.select 'Closed'
    channel_settings.allow_guest_posts[0].click
    channel_settings.require_captcha[0].click

    channel_settings.enable_versioning[0].click
    channel_settings.max_revisions.set '20'
    channel_settings.clear_versioning_data.click

    channel_settings.comment_notify_authors[0].click
    channel_settings.channel_notify[0].click
    channel_settings.channel_notify_emails.set 'trey@treyanastasio.com,mike@mikegordon.com'
    channel_settings.comment_notify[0].click
    channel_settings.comment_notify_emails.set 'page@pagemcconnell.com,jon@jonfishman.com'

    channel_settings.comment_system_enabled[1].click
    channel_settings.apply_comment_enabled_to_existing.click
    channel_settings.deft_comments[1].click
    channel_settings.comment_require_membership[0].click
    channel_settings.comment_require_email[1].click
    channel_settings.comment_use_captcha[1].click
    channel_settings.comment_moderate[0].click
    channel_settings.comment_max_chars.set '40'
    channel_settings.comment_timelock.set '50'
    channel_settings.comment_expiration.set '60'
    channel_settings.apply_expiration_to_existing.click
    channel_settings.comment_text_formatting.select 'None'
    channel_settings.comment_html_formatting.select 'Allow all HTML (not recommended)'
    channel_settings.comment_allow_img_urls[0].click
    channel_settings.comment_auto_link_urls[1].click

    channel_settings.submit
    channel_settings.should have_text 'Channel saved'

    # Create new channel, ensure field groups and things were duplicated
    @page.load
    @page.channel_title.set 'Test'
    @page.duplicate_channel_prefs.select 'News'
    @page.submit

    @page.should have_text 'Channel saved'
    @page.channel_title.value.should == 'Test'
    @page.channel_name.value.should == 'test'

    @page.status_group.value.should == '1'
    @page.field_group.value.should == '1'

    @page.cat_group[0].checked?.should == false
    @page.cat_group[1].checked?.should == true

    # Now make sure settings were duplicated
    channel_settings.load_settings_for_channel(2)
    channel_settings.channel_description.value.should == 'Some description'
    channel_settings.channel_lang.value.should == 'english'

    channel_settings.channel_url.value.should == 'http://someurl/channel'
    channel_settings.comment_url.value.should == 'http://someurl/channel/comment'
    channel_settings.search_results_url.value.should == 'http://someurl/channel/search/results'
    channel_settings.rss_url.value.should == 'http://someurl/channel/rss'
    channel_settings.live_look_template.value.should == '16'

    channel_settings.default_entry_title.value.should == 'Default title'
    channel_settings.url_title_prefix.value.should == 'default-title'
    channel_settings.deft_status.value.should == 'closed'
    channel_settings.deft_category.value.should == '1'
    channel_settings.search_excerpt.value.should == '1'

    channel_settings.channel_html_formatting.value.should == 'none'
    channel_settings.channel_allow_img_urls[1].checked?.should == true
    channel_settings.channel_auto_link_urls[0].checked?.should == true
    channel_settings.show_button_cluster[1].checked?.should == true

    channel_settings.default_status.value.should == 'closed'
    channel_settings.default_author.value.should == '1'
    channel_settings.allow_guest_posts[0].checked?.should == true
    channel_settings.require_captcha[0].checked?.should == true

    channel_settings.enable_versioning[0].checked?.should == true
    channel_settings.max_revisions.value.should == '20'
    channel_settings.clear_versioning_data.checked?.should == false

    channel_settings.comment_notify_authors[0].checked?.should == true
    channel_settings.channel_notify[0].checked?.should == true
    channel_settings.channel_notify_emails.value.should == 'trey@treyanastasio.com,mike@mikegordon.com'
    channel_settings.comment_notify[0].checked?.should == true
    channel_settings.comment_notify_emails.value.should == 'page@pagemcconnell.com,jon@jonfishman.com'

    channel_settings.comment_system_enabled[1].checked?.should == true
    channel_settings.apply_comment_enabled_to_existing.checked?.should == false
    channel_settings.deft_comments[1].checked?.should == true
    channel_settings.comment_require_membership[0].checked?.should == true
    channel_settings.comment_require_email[1].checked?.should == true
    channel_settings.comment_use_captcha[1].checked?.should == true
    channel_settings.comment_moderate[0].checked?.should == true
    channel_settings.comment_max_chars.value.should ==  '40'
    channel_settings.comment_timelock.value.should ==  '50'
    channel_settings.comment_expiration.value.should ==  '60'
    channel_settings.apply_expiration_to_existing.checked?.should == false
    channel_settings.comment_text_formatting.value.should == 'none'
    channel_settings.comment_html_formatting.value.should == 'all'
    channel_settings.comment_allow_img_urls[0].checked?.should == true
    channel_settings.comment_auto_link_urls[1].checked?.should == true
  end
end