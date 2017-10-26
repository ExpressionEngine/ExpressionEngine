require './bootstrap.rb'

feature 'Comment Settings' do

  before(:each) do
    cp_session
    @page = CommentSettings.new
    @page.load
    no_php_js_errors
  end

  it 'shows the Comment Settings page' do
    @page.all_there?.should == true
  end

  it 'should load current settings into form fields' do
    enable_comments = ee_config(item: 'enable_comments')
    comment_word_censoring = ee_config(item: 'comment_word_censoring')
    comment_moderation_override = ee_config(item: 'comment_moderation_override')

    @page.enable_comments_y.checked?.should == (enable_comments == 'y')
    @page.enable_comments_n.checked?.should == (enable_comments == 'n')
    @page.comment_word_censoring_y.checked?.should == (comment_word_censoring == 'y')
    @page.comment_word_censoring_n.checked?.should == (comment_word_censoring == 'n')
    @page.comment_moderation_override.value.should == comment_moderation_override

    @page.comment_edit_time_limit.value.should == '0'
  end

  it 'should validate the form' do
    comment_edit_time_error = 'This field must contain an integer.'

    @page.comment_edit_time_limit.set 'sdfsdfsd'
    @page.submit

    no_php_js_errors
    should_have_form_errors(@page)
    @page.should have_text 'Attention: Settings not saved'
    @page.should have_text comment_edit_time_error

    # AJAX validation
    @page.load
    @page.comment_edit_time_limit.set 'sdfsdfsd'
    @page.comment_edit_time_limit.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_form_errors(@page)
    @page.should have_text comment_edit_time_error

    @page.comment_edit_time_limit.set '100'
    @page.comment_edit_time_limit.trigger 'blur'
    @page.wait_for_error_message_count(0)
    should_have_no_form_errors(@page)
  end

  it 'should save and load the settings' do
    comment_moderation_override = ee_config(item: 'comment_moderation_override')

    @page.enable_comments_n.click
    @page.comment_word_censoring_y.click
    @page.comment_moderation_override_toggle.click
    @page.comment_edit_time_limit.set '300'
    @page.submit

    @page.should have_text 'Preferences updated'
    @page.enable_comments_n.checked?.should == true
    @page.comment_word_censoring_y.checked?.should == true
    @page.comment_moderation_override.value.should_not == comment_moderation_override
    @page.comment_edit_time_limit.value.should == '300'
  end
end
