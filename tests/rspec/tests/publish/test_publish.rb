require './bootstrap.rb'

feature 'Publish Page - Create' do
  before :each do
    cp_session
    @page = Publish.new
    no_php_js_errors
  end

  it 'shows a 404 if there is no channel id' do
    @page.load
    @page.is_404?.should == true
  end

  it 'selects default categories for new entries' do
    @page.load(channel_id: 1)
    @page.tab_links[2].click
    first_category = @page.first('input[name="categories[cat_group_id_1][]"]')
    expect(first_category).to be_checked
  end

  context 'when using file fields' do
    before :each do
      channel_field_form = ChannelFieldForm.new
      channel_field_form.create_field(
        group_id: 1,
        type: 'File',
        label: 'Second File',
        fields: { allowed_directories: 'About' }
      )

      @page.load(channel_id: 1)
      @page.has_title?
      @page.has_url_title?
    end

    it 'the file field properly assigns image data when using the filepicker modal in a channel with two file fields' do
      @page.file_fields.each do |link|
        link.click
        @page.wait_until_modal_visible
        @page.file_modal.wait_for_filters

        @page.file_modal.files[0].click

        @page.wait_until_modal_invisible
      end

      @page.chosen_files.should have_at_least(2).items
    end

    it 'the file field restricts you to the chosen directory' do
      @page.file_fields[1].click

      @page.wait_until_modal_visible
      @page.file_modal.wait_for_filters

      @page.file_modal.filters.should have(2).items
      @page.file_modal.title.should_not == 'All Files'
      @page.file_modal.has_upload_button?

      @page.file_modal.filters[-1].click
      @page.file_modal.view_filters[0].click

      @page.file_modal.wait_for_filters
      @page.file_modal.filters.should have(2).items
      @page.file_modal.title.should_not == 'All Files'
      @page.file_modal.has_upload_button?
    end

    it 'the file field retains data after being created and edited' do
      @page.file_fields.each do |link|
        link.click
        @page.wait_until_modal_visible
        @page.file_modal.wait_for_filters

        @page.file_modal.files[0].click

        @page.wait_until_modal_invisible(1)
      end

      @page.title.set 'File Field Test'
      @page.chosen_files.should have(2).items
      @page.submit_buttons[1].click

      edit = EntryManager.new
      edit.load
      edit.entry_rows[0].find('.toolbar-wrap a[href*="publish/edit/entry"]').click

      @page.chosen_files.should have(2).items
      @page.submit

      edit = EntryManager.new
      edit.load
      edit.entry_rows[0].find('.toolbar-wrap a[href*="publish/edit/entry"]').click

      @page.chosen_files.should have(2).items
    end
  end
end
