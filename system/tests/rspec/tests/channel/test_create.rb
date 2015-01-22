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
  end
end