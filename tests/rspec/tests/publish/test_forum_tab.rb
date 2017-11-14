require './bootstrap.rb'

feature 'Forum Tab' do
  let(:title) { 'Forum Tab Test' }
  let(:body) { 'Lorem ipsum dolor sit amet...' }

  before :each do
    cp_session
    @page = Publish.new
    @page.forum_tab.install_forum
    @page.load(channel_id: 1)
    no_php_js_errors
  end

  it 'has a forum tab' do
    @page.tab_links[4].text.should include 'Forum'
    @page.tab_links[4].click
    @page.forum_tab.should have_forum_title
    @page.forum_tab.should have_forum_body
    @page.forum_tab.should have_forum_id
    @page.forum_tab.should have_forum_topic_id
  end

  it 'creates a forum post when entering data into the forum tab' do
    create_entry

    $db.query('SELECT title, body FROM exp_forum_topics').each do |row|
      row['title'].should == title
      row['body'].should == body
    end

    $db.query('SELECT forum_topic_id FROM exp_channel_titles ORDER BY entry_id desc LIMIT 1').each do |row|
      row['forum_topic_id'].should == 1
    end

    click_link(title)
    @page.tab_links[4].click
    @page.forum_tab.should have_css('textarea[name=forum__forum_body][disabled]')
    @page.forum_tab.should have_css('.fields-select-drop.field-disabled')
  end

  it 'associates a channel entry with a forum post when specifying a forum topic ID' do
    first_entry = nil

    create_entry

    $db.query('SELECT entry_id, count(entry_id) as count FROM exp_channel_titles WHERE forum_topic_id = 1 GROUP BY entry_id').each do |row|
      row['count'].should == 1
      first_entry = row['entry_id']
    end

    @page.load(channel_id: 1)
    @page.title.set 'Second Forum Tab Test'
    @page.tab_links[4].click
    @page.forum_tab.forum_topic_id.set 1
    @page.submit_buttons[1].click

    $db.query('SELECT count(entry_id) as count FROM exp_channel_titles WHERE forum_topic_id = 1').each do |row|
      row['count'].should == 1
      row['entry_id'].should_not == first_entry
    end
  end

  it 'invalidates an entry with both new post content and a forum topic ID' do
    create_entry

    @page.load(channel_id: 1)
    @page.tab_links[4].click
    @page.forum_tab.forum_title.set 'Something'
    @page.forum_tab.forum_body.set 'Lorem ipsum dolor sit amet...'
    @page.forum_tab.forum_topic_id.set '1'
    @page.forum_tab.forum_topic_id.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_form_errors(@page)
    should_have_error_text(
      @page.forum_tab.forum_topic_id,
      'Do not specify a forum Title or Body when setting a Forum Topic ID.'
    )
  end

  it 'invalidates an entry with an invalid forum topic ID' do
    @page.tab_links[4].click
    @page.forum_tab.forum_topic_id.set '999'
    @page.forum_tab.forum_topic_id.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_form_errors(@page)
    should_have_error_text(
      @page.forum_tab.forum_topic_id,
      'There is no forum topic with that ID.'
    )

    @page.forum_tab.forum_topic_id.set ''
    @page.forum_tab.forum_topic_id.trigger 'blur'
    @page.wait_for_error_message_count(0)
    should_have_no_form_errors(@page)
    should_have_no_error_text(@page.forum_tab.forum_topic_id)
  end

  it 'requires both the forum title and body when creating new forum topics' do
    @page.tab_links[4].click
    @page.forum_tab.forum_title.set title
    @page.forum_tab.forum_title.trigger 'blur'
    @page.forum_tab.forum_body.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_form_errors(@page)
    should_have_error_text(
      @page.forum_tab.forum_body,
      'You cannot create a forum topic without content.'
    )

    @page.forum_tab.forum_title.set ''
    @page.forum_tab.forum_title.trigger 'blur'
    @page.forum_tab.forum_body.trigger 'blur'
    @page.wait_for_error_message_count(0)
    should_have_no_form_errors(@page)
    should_have_no_error_text(@page.forum_tab.forum_body)

    @page.forum_tab.forum_body.set body
    @page.forum_tab.forum_body.trigger 'blur'
    @page.forum_tab.forum_title.trigger 'blur'
    @page.wait_for_error_message_count(1)
    should_have_form_errors(@page)
    should_have_error_text(
      @page.forum_tab.forum_title,
      'You must give the forum topic a title.'
    )
  end

  # https://expressionengine.com/support/bugs/23253/editing-entry-with-forum-post
  it 'edits an entry successfully that has forum content in the forum tab' do
    create_entry

    edit = EntryManager.new
    edit.load
    edit.entry_rows[0].find('.toolbar-wrap a[href*="publish/edit/entry"]').click

    @page.title.set title + " Edited"
    @page.submit_buttons[1].click

    @page.all_there?.should == false
    @page.alert.has_content?("The entry #{title} Edited has been updated.").should == true
  end

  def create_entry
    @page.title.set title
    @page.tab_links[4].click
    @page.forum_tab.forum_title.set title
    @page.forum_tab.forum_body.set body
    @page.forum_tab.forum_id.click
    @page.forum_tab.forum_id_choices[0].click

    @page.submit_buttons[2].click

    @page.all_there?.should == false
    @page.alert.has_content?("The entry #{title} has been created.").should == true
  end
end
